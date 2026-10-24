<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Actions\ExpirarSolicitudesDeClasificacion;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->persona = Persona::factory()->create();
    $this->conceptoExamen = Concepto::factory()->create(['tipo' => TipoConcepto::ExamenClasificacion]);
    $this->pago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $this->persona->id]);
    $this->coordinador = User::factory()->create();
});

function solicitarExamen(?int $idiomaId = null, ?int $pagoId = null): SolicitudClasificacion
{
    return app(SolicitarExamenDeClasificacion::class)(test()->persona->id, $idiomaId ?? test()->ingles->id, $pagoId ?? test()->pago->id);
}

function resultado(array $cambios = []): ResultadoDeClasificacion
{
    $datos = array_map(fn (mixed $valor): mixed => $valor instanceof Closure ? $valor() : $valor, [
        'fechaExamen' => '2026-10-24', 'puntaje' => '78.50', 'cicloId' => test()->cicloVI->id, 'observaciones' => 'Buena comprensión lectora', ...$cambios,
    ]);

    return new ResultadoDeClasificacion(CarbonImmutable::parse($datos['fechaExamen']), $datos['puntaje'], $datos['cicloId'], $datos['observaciones']);
}

it('describes the languages with their cycles', function (): void {
    Idioma::factory()->create(['nombre' => 'Quechua', 'activo' => false]);

    $ingles = collect(app(CatalogoDeIdiomas::class)())->firstWhere('id', $this->ingles->id);

    expect($ingles->nombre)->toBe('Inglés')
        ->and($ingles->activo)->toBeTrue()
        ->and($ingles->ciclos)->toBe([$this->cicloVI->id => 'Ciclo 6 (Intermedio)']);
});

it('creates a pending request that expires in 30 days with an unused validated placement payment', function (): void {
    $solicitud = solicitarExamen();

    expect($solicitud->refresh())
        ->persona_id->toBe($this->persona->id)
        ->idioma_id->toBe($this->ingles->id)
        ->pago_id->toBe($this->pago->id)
        ->estado->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and($solicitud->vence_en->toDateString())->toBe('2026-11-23');
});

it('refuses a request that breaks a rule and creates nothing', function (Closure $preparar, string $mensaje): void {
    [$idiomaId, $pagoId] = $preparar($this);

    expect(fn (): SolicitudClasificacion => solicitarExamen($idiomaId, $pagoId))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(SolicitudClasificacion::query()->count())->toBe(0);
})->with([
    'another person payment' => [fn ($t): array => [null, Pago::factory()->validado()->for($t->conceptoExamen)->create()->id], 'El pago pertenece a otra persona.'],
    'pending payment' => [fn ($t): array => [null, Pago::factory()->for($t->conceptoExamen)->create(['persona_id' => $t->persona->id])->id], 'El pago no está validado.'],
    'enrollment payment' => [fn ($t): array => [null, Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $t->persona->id])->id], 'El pago no corresponde a este servicio.'],
    'inactive language' => [fn ($t): array => [Idioma::factory()->create(['activo' => false])->id, null], 'El idioma no está disponible para el examen de clasificación.'],
]);

it('refuses a payment already used and a second pending request for the same language', function (): void {
    $otroIdioma = Idioma::factory()->create();
    solicitarExamen();

    expect(fn (): SolicitudClasificacion => solicitarExamen($otroIdioma->id))->toThrow(ReglaDeNegocioIncumplida::class, 'El pago ya se usó en otra solicitud.');

    $otroPago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $this->persona->id]);

    expect(fn (): SolicitudClasificacion => solicitarExamen(pagoId: $otroPago->id))->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tiene una solicitud pendiente de evaluación para Inglés.')
        ->and(SolicitudClasificacion::query()->count())->toBe(1);
});

it('records the result and enables the assigned cycle for that language, replacing the previous one', function (): void {
    $solicitud = solicitarExamen();
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'ciclo_id' => Ciclo::factory()->for($this->ingles)->create(['numero' => 2])->id, 'origen' => OrigenHabilitacion::Aprobacion]);

    app(RegistrarResultadoDeClasificacion::class)($solicitud->id, resultado(), $this->coordinador);

    $solicitud->refresh();
    $habilitacion = Habilitacion::query()->where(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id])->sole();

    expect($solicitud)
        ->estado->toBe(EstadoSolicitudClasificacion::Evaluado)
        ->puntaje->toBe('78.50')
        ->ciclo_asignado_id->toBe($this->cicloVI->id)
        ->evaluado_por->toBe($this->coordinador->id)
        ->observaciones->toBe('Buena comprensión lectora')
        ->and($solicitud->fecha_examen->toDateString())->toBe('2026-10-24')
        ->and($solicitud->evaluado_en)->not->toBeNull()
        ->and($habilitacion)
        ->ciclo_id->toBe($this->cicloVI->id)
        ->origen->toBe(OrigenHabilitacion::Clasificacion)
        ->actualizado_por->toBe($this->coordinador->id);
});

it('refuses a result that breaks a rule and changes nothing', function (Closure $preparar, array $cambios, string $mensaje): void {
    $solicitud = $preparar($this);
    $antes = [$solicitud->refresh()->toArray(), Habilitacion::query()->count()];

    expect(fn () => app(RegistrarResultadoDeClasificacion::class)($solicitud->id, resultado($cambios), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and([$solicitud->refresh()->toArray(), Habilitacion::query()->count()])->toBe($antes);
})->with([
    'already evaluated' => [fn ($t) => SolicitudClasificacion::factory()->evaluada()->create(['persona_id' => $t->persona->id, 'idioma_id' => $t->ingles->id, 'ciclo_asignado_id' => $t->cicloVI->id]), [], 'La solicitud ya está Evaluado.'],
    'expired' => [fn ($t) => SolicitudClasificacion::factory()->create(['persona_id' => $t->persona->id, 'idioma_id' => $t->ingles->id, 'estado' => EstadoSolicitudClasificacion::Expirado]), [], 'La solicitud ya está Expirado.'],
    'cycle of another language' => [fn ($t): SolicitudClasificacion => solicitarExamen(), ['cicloId' => fn () => Ciclo::factory()->create()->id], 'El ciclo asignado no pertenece a Inglés.'],
    'score above 100' => [fn ($t): SolicitudClasificacion => solicitarExamen(), ['puntaje' => '100.01'], 'El puntaje va de 0 a 100, con hasta dos decimales.'],
    'score with three decimals' => [fn ($t): SolicitudClasificacion => solicitarExamen(), ['puntaje' => '70.125'], 'El puntaje va de 0 a 100, con hasta dos decimales.'],
    'future exam date' => [fn ($t): SolicitudClasificacion => solicitarExamen(), ['fechaExamen' => '2026-10-25'], 'La fecha del examen debe estar entre el 24/10/2026 y el 24/10/2026.'],
    'exam before the request' => [fn ($t): SolicitudClasificacion => solicitarExamen(), ['fechaExamen' => '2026-10-23'], 'La fecha del examen debe estar entre el 24/10/2026 y el 24/10/2026.'],
]);

it('expires pending requests past their date and leaves the rest', function (): void {
    $vencida = SolicitudClasificacion::factory()->create(['vence_en' => '2026-10-23']);
    $vigente = SolicitudClasificacion::factory()->create(['vence_en' => '2026-10-24']);
    $evaluada = SolicitudClasificacion::factory()->evaluada()->create(['vence_en' => '2026-10-01']);

    expect(app(ExpirarSolicitudesDeClasificacion::class)())->toBe(1)
        ->and($vencida->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Expirado)
        ->and($vigente->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and($evaluada->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Evaluado);

    $this->artisan('clasificacion:expirar')->expectsOutput('Solicitudes de clasificación vencidas: 0')->assertSuccessful();
});

it('schedules the daily expiration of placement requests', function (): void {
    $tarea = collect(app(Schedule::class)->events())->first(fn ($evento): bool => str_contains((string) $evento->command, 'clasificacion:expirar'));

    expect($tarea)->not->toBeNull()
        ->and($tarea->expression)->toBe('10 0 * * *')
        ->and($tarea->timezone)->toBe('America/Lima');
});

it('accepts scores at both ends of the scale', function (string $puntaje): void {
    $solicitud = solicitarExamen();

    app(RegistrarResultadoDeClasificacion::class)($solicitud->id, resultado(['puntaje' => $puntaje]), $this->coordinador);

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Evaluado);
})->with(['0', '100', '100.00', '0.5']);

it('refuses malformed scores', function (string $puntaje): void {
    $solicitud = solicitarExamen();

    expect(fn () => app(RegistrarResultadoDeClasificacion::class)($solicitud->id, resultado(['puntaje' => $puntaje]), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El puntaje va de 0 a 100, con hasta dos decimales.');
})->with(['-1', '050', '100.1', '101', 'abc', '']);
