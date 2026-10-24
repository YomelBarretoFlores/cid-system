<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\RetirarMatricula;
use App\Modules\Matricula\Actions\TrasladarMatricula;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create();
    $this->ciclo = Ciclo::factory()->create();
    $this->origen = Grupo::factory()->for($this->periodo)->for($this->ciclo)->create(['codigo' => 'ING-I-A', 'matriculados' => 1]);
    $this->destino = Grupo::factory()->for($this->periodo)->for($this->ciclo)->create(['codigo' => 'ING-I-B', 'cupo_maximo' => 20, 'matriculados' => 5]);
    $this->matricula = Matricula::factory()->create([
        'grupo_id' => $this->origen->id, 'condicion' => CondicionMatricula::Repitente, 'matriculado_en' => '2026-10-20 15:30:00+00',
    ]);
    $this->coordinador = User::factory()->create();
});

/**
 * @return array<string, mixed>
 */
function estadoDeTraslados(): array
{
    return [
        'matriculas' => Matricula::query()->orderBy('id')->get(['grupo_id', 'estado', 'retirado_en'])->toArray(),
        'grupos' => Grupo::query()->orderBy('id')->pluck('matriculados', 'id')->all(),
        'movimientos' => Movimiento::query()->count(),
    ];
}

it('moves an active enrollment to another group of the same period and cycle and records who authorized it', function (): void {
    $this->travelTo('2026-10-25 14:00:00');

    app(TrasladarMatricula::class)($this->matricula->id, $this->destino->id, '  Cruce de horarios con el trabajo  ', $this->coordinador);

    $matricula = $this->matricula->refresh();
    $movimiento = Movimiento::query()->sole();

    expect($matricula->grupo_id)->toBe($this->destino->id)
        ->and($matricula->estado)->toBe(EstadoMatricula::Activa)
        ->and($matricula->condicion)->toBe(CondicionMatricula::Repitente)
        ->and($matricula->matriculado_en->toIso8601String())->toBe('2026-10-20T15:30:00+00:00')
        ->and([$this->origen->refresh()->matriculados, $this->destino->refresh()->matriculados])->toBe([0, 6])
        ->and($movimiento)
        ->matricula_id->toBe($matricula->id)
        ->tipo->toBe(TipoMovimiento::Traslado)
        ->grupo_origen_id->toBe($this->origen->id)
        ->grupo_destino_id->toBe($this->destino->id)
        ->motivo->toBe('Cruce de horarios con el trabajo')
        ->autorizado_por->toBe($this->coordinador->id)
        ->and($movimiento->efectuado_en->toIso8601String())->toBe('2026-10-25T14:00:00+00:00');
});

it('transfers into a group closed to enrollment', function (): void {
    $this->destino->update(['estado' => EstadoGrupo::Cerrado]);

    app(TrasladarMatricula::class)($this->matricula->id, $this->destino->id, 'Cierre del grupo de origen', $this->coordinador);

    expect($this->matricula->refresh()->grupo_id)->toBe($this->destino->id);
});

it('refuses a transfer that breaks a rule and changes nothing', function (Closure $preparar, string $mensaje): void {
    $destinoId = $preparar($this);
    $antes = estadoDeTraslados();

    expect(fn () => app(TrasladarMatricula::class)($this->matricula->id, $destinoId, 'Cierre del grupo', $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(estadoDeTraslados())->toBe($antes);
})->with([
    'same group' => [fn ($t) => $t->origen->id, 'El grupo destino debe ser distinto del grupo actual.'],
    'another cycle' => [fn ($t) => Grupo::factory()->for($t->periodo)->create()->id, 'El grupo destino debe ser del mismo periodo y ciclo.'],
    'another period' => [fn ($t) => Grupo::factory()->for($t->ciclo)->create()->id, 'El grupo destino debe ser del mismo periodo y ciclo.'],
    'scheduled destination' => [fn ($t) => tap($t->destino)->update(['estado' => EstadoGrupo::Programado])->id, 'El grupo ING-I-B está Programado y no recibe traslados.'],
    'cancelled destination' => [fn ($t) => tap($t->destino)->update(['estado' => EstadoGrupo::Cancelado])->id, 'El grupo ING-I-B está Cancelado y no recibe traslados.'],
    'full destination' => [fn ($t) => tap($t->destino)->update(['cupo_maximo' => 5, 'cupo_minimo' => 1])->id, 'El grupo ING-I-B no tiene vacantes disponibles.'],
    'withdrawn enrollment' => [fn ($t) => tap($t->destino, fn () => $t->matricula->update(['estado' => EstadoMatricula::Retirada, 'retirado_en' => now()]))->id, 'Solo se puede trasladar o retirar una matrícula activa.'],
]);

it('requires a reason of at most 500 characters', function (string $motivo, string $mensaje): void {
    expect(fn () => app(TrasladarMatricula::class)($this->matricula->id, $this->destino->id, $motivo, $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(fn () => app(RetirarMatricula::class)($this->matricula->id, $motivo, $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Movimiento::query()->count())->toBe(0);
})->with([
    'blank' => ['   ', 'Escribe el motivo del traslado o retiro.'],
    'too long' => [str_repeat('a', 501), 'El motivo admite como máximo 500 caracteres.'],
]);

it('withdraws an active enrollment, frees its seat and records who authorized it', function (): void {
    $this->travelTo('2026-10-25 14:00:00');

    app(RetirarMatricula::class)($this->matricula->id, 'Viaje por trabajo', $this->coordinador);

    $matricula = $this->matricula->refresh();
    $movimiento = Movimiento::query()->sole();

    expect($matricula->estado)->toBe(EstadoMatricula::Retirada)
        ->and($matricula->retirado_en?->toIso8601String())->toBe('2026-10-25T14:00:00+00:00')
        ->and($matricula->grupo_id)->toBe($this->origen->id)
        ->and($this->origen->refresh()->matriculados)->toBe(0)
        ->and($movimiento)
        ->tipo->toBe(TipoMovimiento::Retiro)
        ->grupo_origen_id->toBe($this->origen->id)
        ->grupo_destino_id->toBeNull()
        ->motivo->toBe('Viaje por trabajo')
        ->autorizado_por->toBe($this->coordinador->id);
});

it('refuses a withdrawal that breaks a rule and changes nothing', function (Closure $preparar, string $mensaje): void {
    $preparar($this);
    $antes = estadoDeTraslados();

    expect(fn () => app(RetirarMatricula::class)($this->matricula->id, 'Viaje por trabajo', $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(estadoDeTraslados())->toBe($antes);
})->with([
    'already withdrawn' => [fn ($t) => $t->matricula->update(['estado' => EstadoMatricula::Retirada, 'retirado_en' => now()]), 'Solo se puede trasladar o retirar una matrícula activa.'],
    'finished group' => [fn ($t) => $t->origen->update(['estado' => EstadoGrupo::Finalizado]), 'El grupo ING-I-A está Finalizado y ya no admite retiros.'],
]);
