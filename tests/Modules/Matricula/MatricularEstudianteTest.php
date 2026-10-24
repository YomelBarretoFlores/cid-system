<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Events\EstudianteMatriculado;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([EstudianteMatriculado::class]);
    $this->idioma = Idioma::factory()->create();
    $this->ciclo1 = Ciclo::factory()->for($this->idioma)->create(['numero' => 1]);
    $this->ciclo2 = Ciclo::factory()->for($this->idioma)->create(['numero' => 2]);
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $this->grupo = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create(['cupo_maximo' => 20]);
    $this->persona = Persona::factory()->create();
    $this->conceptoMatricula = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    $this->pago = Pago::factory()->validado()->state(['persona_id' => $this->persona->id])->for($this->conceptoMatricula)->create();
});

function matricular(?int $grupoId = null, ?int $pagoId = null): Matricula
{
    return app(MatricularEstudiante::class)(test()->persona->id, $grupoId ?? test()->grupo->id, $pagoId ?? test()->pago->id);
}

function enLima(CarbonInterface $fecha, string $hora): CarbonImmutable
{
    return CarbonImmutable::parse("{$fecha->toDateString()} {$hora}", 'America/Lima');
}

function expectBloqueo(Closure $intento, string $mensaje): void
{
    $matriculados = test()->grupo->refresh()->matriculados;

    expect($intento)->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect(test()->grupo->refresh()->matriculados)->toBe($matriculados)
        ->and(Matricula::query()->where('pago_id', test()->pago->id)->exists())->toBeFalse();
    Event::assertNotDispatched(EstudianteMatriculado::class);
}

it('enrolls a new student in the first cycle as regular, taking a vacancy and the payment', function (): void {
    $this->grupo->update(['cupo_maximo' => 1, 'cupo_minimo' => 1]);

    $matricula = matricular();

    expect($matricula->refresh())
        ->persona_id->toBe($this->persona->id)
        ->grupo_id->toBe($this->grupo->id)
        ->pago_id->toBe($this->pago->id)
        ->condicion->toBe(CondicionMatricula::Regular)
        ->estado->toBe(EstadoMatricula::Activa)
        ->and($this->grupo->refresh()->matriculados)->toBe(1)
        ->and($this->grupo->vacantes_disponibles)->toBe(0);
    Event::assertDispatched(EstudianteMatriculado::class, fn (EstudianteMatriculado $evento): bool => $evento->matriculaId === $matricula->id
        && $evento->personaId === $this->persona->id
        && $evento->grupoId === $this->grupo->id);
});

it('blocks a cycle the student is not enabled for', function (): void {
    $grupoCiclo2 = Grupo::factory()->for($this->periodo)->for($this->ciclo2)->create();

    expectBloqueo(fn (): Matricula => matricular($grupoCiclo2->id), 'No está habilitado para este ciclo');
});

it('enrolls in the cycle enabled by placement or approval, and only in that one', function (): void {
    $grupoCiclo2 = Grupo::factory()->for($this->periodo)->for($this->ciclo2)->create();
    Habilitacion::factory()->state(['persona_id' => $this->persona->id])->create(['ciclo_id' => $this->ciclo2->id]);

    expectBloqueo(fn (): Matricula => matricular(), 'No está habilitado para este ciclo');
    expect(matricular($grupoCiclo2->id)->grupo_id)->toBe($grupoCiclo2->id);
});

it('blocks a payment that is not validated', function (string $estado): void {
    $this->pago = Pago::factory()->{$estado}()->state(['persona_id' => $this->persona->id])->for($this->conceptoMatricula)->create();

    expectBloqueo(fn (): Matricula => matricular(), 'El pago no está validado.');
})->with(['pendiente', 'rechazado', 'anulado']);

it('blocks a payment of another person or for another service', function (Closure $pago, string $mensaje): void {
    $this->pago = $pago();

    expectBloqueo(fn (): Matricula => matricular(), $mensaje);
})->with([
    'another person' => [fn (): Pago => Pago::factory()->validado()->for(test()->conceptoMatricula)->create(), 'El pago pertenece a otra persona.'],
    'another service' => [fn (): Pago => Pago::factory()->validado()->state(['persona_id' => test()->persona->id])
        ->for(Concepto::factory()->state(['tipo' => TipoConcepto::Certificado]))->create(), 'El pago no corresponde a este servicio.'],
]);

it('blocks a group without vacancies', function (): void {
    $this->grupo->update(['cupo_maximo' => 1, 'cupo_minimo' => 1]);
    $otro = Persona::factory()->create();
    app(MatricularEstudiante::class)($otro->id, $this->grupo->id, Pago::factory()->validado()->for($this->conceptoMatricula)->state(['persona_id' => $otro->id])->create()->id);
    Event::fake([EstudianteMatriculado::class]);

    expectBloqueo(fn (): Matricula => matricular(), 'El grupo no tiene vacantes disponibles.');
});

it('blocks a group whose enrollment is not open', function (Closure $cerrar): void {
    $cerrar();

    expectBloqueo(fn (): Matricula => matricular(), 'La matrícula de este grupo no está abierta.');
})->with([
    'group not opened' => [fn () => test()->grupo->update(['estado' => EstadoGrupo::Programado])],
    'group cancelled' => [fn () => test()->grupo->update(['estado' => EstadoGrupo::Cancelado])],
    'period not in enrollment' => [fn () => test()->periodo->update(['estado' => EstadoPeriodo::EnCurso])],
    'day after the window in Lima' => [fn () => test()->travelTo(enLima(test()->periodo->matricula_fin->addDay(), '00:30'))],
    'day before the window in Lima' => [fn () => test()->travelTo(enLima(test()->periodo->matricula_inicio->subDay(), '23:30'))],
]);

it('keeps enrollment open until the last day ends in Lima', function (): void {
    $this->travelTo(enLima($this->periodo->matricula_fin, '23:30'));

    expect(matricular()->exists)->toBeTrue();
});

it('blocks a payment already used for another enrollment', function (): void {
    $otroGrupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->for($this->ciclo1)->create();
    Matricula::factory()->state(['persona_id' => $this->persona->id])->create(['grupo_id' => $otroGrupo->id, 'pago_id' => $this->pago->id]);

    expect(fn (): Matricula => matricular())->toThrow(ReglaDeNegocioIncumplida::class, 'El pago ya se usó en otra matrícula.');
    expect($this->grupo->refresh()->matriculados)->toBe(0);
});

it('treats a repeated request as a harmless retry', function (): void {
    $primera = matricular();

    $segunda = matricular();

    expect($segunda->is($primera))->toBeTrue()
        ->and($this->grupo->refresh()->matriculados)->toBe(1)
        ->and(Matricula::query()->count())->toBe(1);
    Event::assertDispatchedTimes(EstudianteMatriculado::class);
});

it('blocks a second group of the same cycle in the same period', function (): void {
    $otroGrupo = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create();
    Matricula::factory()->state(['persona_id' => $this->persona->id])->create(['grupo_id' => $otroGrupo->id]);

    expectBloqueo(fn (): Matricula => matricular(), 'Ya tiene una matrícula activa en este ciclo y periodo.');
});

it('marks as repeating a student who already took the cycle in an earlier period', function (string $estadoAnterior, CondicionMatricula $condicion): void {
    $grupoAnterior = Grupo::factory()->for(Periodo::factory())->for($this->ciclo1)->create(['estado' => EstadoGrupo::Finalizado]);
    Matricula::factory()->state(['persona_id' => $this->persona->id])->{$estadoAnterior}()->create(['grupo_id' => $grupoAnterior->id]);

    expect(matricular()->condicion)->toBe($condicion);
})->with([
    'completed before' => ['activa', CondicionMatricula::Repitente],
    'withdrew before' => ['retirada', CondicionMatricula::Regular],
]);
