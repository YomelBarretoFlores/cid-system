<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
});

function solicitar(Persona $persona, string $codigo, DatosDeSolicitud $datos = new DatosDeSolicitud): Tramite
{
    return app(SolicitarTramite::class)($persona->id, TipoTramite::query()->where('codigo', $codigo)->value('id'), $datos);
}

it('opens a trámite with a fee as pending payment, on its first stage, with a copy of its stages and the chosen enrollment', function (): void {
    Event::fake([TramiteActualizado::class]);

    $tramite = solicitar($this->persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $this->matricula->id, comentario: '  Para mi empleador.  '));

    expect($tramite)
        ->codigo->toStartWith('TR-')
        ->estado->toBe(EstadoTramite::PendientePago)
        ->etapa_actual->toBe(0)
        ->tipo_nombre->toBe('Constancia de matrícula')
        ->accion_final->toBe(AccionFinal::ConstanciaMatricula)
        ->and(array_column($tramite->etapas, 'avance'))->toBe([0, 33, 66, 100])
        ->and($tramite->datos)->toMatchArray(['matricula_id' => $this->matricula->id, 'comentario' => 'Para mi empleador.'])
        ->and($tramite->datos['matricula'])->toBeString()->not->toBeEmpty()
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->sole())
        ->estado->toBe(EstadoTramite::PendientePago)->etapa_nombre->toBe('Solicitud recibida')->actor_id->toBeNull();

    Event::assertDispatched(TramiteActualizado::class, fn (TramiteActualizado $evento): bool => $evento->tramiteId === $tramite->id);
});

it('opens a trámite without a fee in progress, and asks a withdrawal for its reason', function (): void {
    expect(fn (): Tramite => solicitar($this->persona, 'RETIRO', new DatosDeSolicitud(matriculaId: $this->matricula->id, motivo: 'no')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo, entre 5 y 500 caracteres.');

    $tramite = solicitar($this->persona, 'RETIRO', new DatosDeSolicitud(matriculaId: $this->matricula->id, motivo: 'Cambio de horario de trabajo'));

    expect($tramite)->estado->toBe(EstadoTramite::EnProceso)
        ->and($tramite->datos['motivo'])->toBe('Cambio de horario de trabajo');
});

it('refuses an enrollment that is not active or not the student\'s', function (Closure $matricula): void {
    expect(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $matricula($this->persona)?->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Elige una de tus matrículas activas.');
})->with([
    'no enrollment' => [fn (): ?Matricula => null],
    'withdrawn' => [fn (Persona $persona): Matricula => Matricula::factory()->retirada()->create(['persona_id' => $persona->id])],
    'someone else\'s' => [fn (): Matricula => Matricula::factory()->activa()->create()],
]);

it('asks a grade record for a level with a passed cycle and a certificate for a completed level without a valid certificate', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    ActasFinales::historial($this->persona, 'Quechua', [[1, '12.00', 'aprobado'], [5, '08.00', 'desaprobado']]);

    expect(solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Quechua', nivel: NivelCurricular::Basico->value))->datos)->toMatchArray(['idioma' => 'Quechua', 'nivel' => 'basico'])
        ->and(solicitar($this->persona, 'CERTIFICADO-NIVEL', new DatosDeSolicitud(idioma: 'Inglés', nivel: NivelCurricular::Basico->value))->datos)
        ->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'basico'])
        ->and(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Francés', nivel: NivelCurricular::Basico->value)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Aún no apruebas ningún ciclo de ese idioma y nivel.')
        ->and(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Quechua', nivel: NivelCurricular::Intermedio->value)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Aún no apruebas ningún ciclo de ese idioma y nivel.')
        ->and(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Quechua')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Aún no apruebas ningún ciclo de ese idioma y nivel.')
        ->and(fn (): Tramite => solicitar($this->persona, 'CERTIFICADO-NIVEL', new DatosDeSolicitud(idioma: 'Quechua', nivel: NivelCurricular::Basico->value)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Aún no completas ese nivel o ya tienes su certificado vigente.');
});

it('keeps one unfinished grade record trámite per language and level, and allows another level meanwhile', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [5, '14.00', 'aprobado']]);
    solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Inglés', nivel: NivelCurricular::Basico->value));

    expect(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Inglés', nivel: NivelCurricular::Basico->value)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tienes un trámite de Constancia de notas en curso para lo mismo.')
        ->and(solicitar($this->persona, 'CONSTANCIA-NOTAS', new DatosDeSolicitud(idioma: 'Inglés', nivel: NivelCurricular::Intermedio->value))->datos)
        ->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'intermedio']);
});

it('keeps one unfinished trámite per type and subject, and allows a new one once it is finished', function (): void {
    $primero = solicitar($this->persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $this->matricula->id));

    expect(fn (): Tramite => solicitar($this->persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $this->matricula->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tienes un trámite de Constancia de matrícula en curso para lo mismo.');

    DB::table('tra_tramites')->where('id', $primero->id)->update(['estado' => 'finalizado', 'etapa_actual' => 3, 'finalizado_en' => now()]);

    expect(solicitar($this->persona, 'CONSTANCIA-MATRICULA', new DatosDeSolicitud(matriculaId: $this->matricula->id))->id)->not->toBe($primero->id);
});

it('refuses a type that is not available or whose stages are broken, and an overlong comment', function (): void {
    $inactivo = TipoTramite::factory()->sinTasa()->create(['activo' => false, 'accion_final' => AccionFinal::Ninguna]);
    $roto = TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Ninguna]);
    $roto->etapas()->where('orden', 3)->update(['avance' => 90]);
    $libre = TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Ninguna]);

    expect(fn () => app(SolicitarTramite::class)($this->persona->id, $inactivo->id, new DatosDeSolicitud))->toThrow(ReglaDeNegocioIncumplida::class, 'Este trámite no está disponible.')
        ->and(fn () => app(SolicitarTramite::class)($this->persona->id, $roto->id, new DatosDeSolicitud))->toThrow(ReglaDeNegocioIncumplida::class, 'La última etapa debe tener 100 % de avance.')
        ->and(fn () => app(SolicitarTramite::class)($this->persona->id, $libre->id, new DatosDeSolicitud(comentario: str_repeat('a', 501))))->toThrow(ReglaDeNegocioIncumplida::class, 'El comentario admite hasta 500 caracteres.')
        ->and(Tramite::query()->count())->toBe(0);
});
