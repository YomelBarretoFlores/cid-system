<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;

afterEach(function (): void {
    $this->truncateTablesForAllConnections();
});

it('gives the last vacancy to exactly one of two simultaneous requests', function (): void {
    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->create(['cupo_maximo' => 1, 'cupo_minimo' => 1]);

    expect(enParalelo('reservar-vacante.php', [[$grupo->id], [$grupo->id]]))->toBe(['El grupo no tiene vacantes disponibles.', 'reservada'])
        ->and($grupo->refresh()->matriculados)->toBe(1);
});

it('enrolls a person only once per cycle and period when two groups are requested at the same time', function (): void {
    $ciclo = Ciclo::factory()->create(['numero' => 1]);
    $periodo = Periodo::factory()->conMatriculaAbierta()->create();
    [$grupoA, $grupoB] = Grupo::factory()->count(2)->for($periodo)->for($ciclo)->create();
    $persona = Persona::factory()->create();
    // Payments refuse TRUNCATE, so these rows stay until the next migrate:fresh.
    [$pagoA, $pagoB] = Pago::factory()->count(2)->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);

    $salidas = enParalelo('matricular.php', [[$persona->id, $grupoA->id, $pagoA->id], [$persona->id, $grupoB->id, $pagoB->id]]);

    expect($salidas)->toBe(['Ya tiene una matrícula activa en este ciclo y periodo.', 'matriculada'])
        ->and(Matricula::query()->where('persona_id', $persona->id)->count())->toBe(1)
        ->and($grupoA->refresh()->matriculados + $grupoB->refresh()->matriculados)->toBe(1);
});
