<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create();
    $this->ciclo = Ciclo::factory()->create();
});

function grupoDelCiclo(Periodo $periodo, Ciclo $ciclo, int $cupo, int $matriculados, string $codigo): Grupo
{
    return Grupo::factory()->for($periodo)->for($ciclo)->create([
        'codigo' => $codigo, 'cupo_maximo' => $cupo, 'cupo_minimo' => 1, 'matriculados' => $matriculados,
    ]);
}

it('gives the last vacancy of a group to exactly one of two simultaneous transfers', function (): void {
    $destino = grupoDelCiclo($this->periodo, $this->ciclo, 3, 2, 'TR-DESTINO');
    $origenA = grupoDelCiclo($this->periodo, $this->ciclo, 5, 1, 'TR-A');
    $origenB = grupoDelCiclo($this->periodo, $this->ciclo, 5, 1, 'TR-B');

    expect(enParalelo('mover-vacante.php', [[$origenA->id, $destino->id], [$origenB->id, $destino->id]]))
        ->toBe(['El grupo TR-DESTINO no tiene vacantes disponibles.', 'trasladada'])
        ->and($destino->refresh()->matriculados)->toBe(3)
        ->and($origenA->refresh()->matriculados + $origenB->refresh()->matriculados)->toBe(1);
});

it('completes two simultaneous transfers in opposite directions without a deadlock', function (): void {
    $grupoA = grupoDelCiclo($this->periodo, $this->ciclo, 5, 2, 'TR-A');
    $grupoB = grupoDelCiclo($this->periodo, $this->ciclo, 5, 2, 'TR-B');

    expect(enParalelo('mover-vacante.php', [[$grupoA->id, $grupoB->id], [$grupoB->id, $grupoA->id]]))
        ->toBe(['trasladada', 'trasladada'])
        ->and([$grupoA->refresh()->matriculados, $grupoB->refresh()->matriculados])->toBe([2, 2]);
});
