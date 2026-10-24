<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;

beforeEach(function (): void {
    $this->usuario = User::factory()->create();
    $this->periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $this->docente = Docente::factory()->create();
    $this->docente->idiomas()->create(['idioma_id' => $this->ciclo->idioma_id, 'nivel_maximo' => NivelCurricular::Basico]);
    $this->franja = Disponibilidad::query()->create(['docente_id' => $this->docente->id, 'dia_semana' => 1, 'hora_inicio' => '16:00:00', 'hora_fin' => '22:00:00']);
});

function grupoDeLunes(Periodo $periodo, Ciclo $ciclo, string $codigo, string $inicio, string $fin): Grupo
{
    $grupo = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => $codigo, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->create(['grupo_id' => $grupo->id, 'dia_semana' => 1, 'hora_inicio' => $inicio, 'hora_fin' => $fin]);

    return $grupo;
}

it('gives one teacher only one of two clashing groups assigned at the same time', function (): void {
    $a = grupoDeLunes($this->periodo, $this->ciclo, 'CD-A', '18:00:00', '20:00:00');
    $b = grupoDeLunes($this->periodo, $this->ciclo, 'CD-B', '19:00:00', '21:00:00');

    $salidas = enParalelo('asignar-docente.php', [[$a->id, $this->docente->id, $this->usuario->id], [$b->id, $this->docente->id, $this->usuario->id]]);

    expect($salidas)->toHaveCount(2)
        ->and($salidas)->toContain('asignado')
        ->and(collect($salidas)->contains(fn (string $salida): bool => str_contains($salida, 'que se cruza con el grupo')))->toBeTrue()
        ->and(CargaAcademica::query()->where('docente_id', $this->docente->id)->count())->toBe(1);
});

it('never leaves an assigned session outside the availability when the window is removed at the same time', function (): void {
    $grupo = grupoDeLunes($this->periodo, $this->ciclo, 'CD-C', '18:00:00', '20:00:00');

    $salidas = enParalelo('asignar-o-quitar-disponibilidad.php', [
        ['asignar', $this->docente->id, $grupo->id, $this->usuario->id],
        ['quitar', $this->docente->id, $this->franja->id, $this->usuario->id],
    ]);

    $asignado = CargaAcademica::query()->where('grupo_id', $grupo->id)->exists();
    $franjaVigente = Disponibilidad::query()->whereKey($this->franja->id)->exists();

    expect($salidas)->toBeIn([
        ['asignar: hecho', 'quitar: La sesión del Lunes de 18:00 a 20:00 del grupo CD-C está fuera de la disponibilidad del docente.'],
        ['asignar: La sesión del Lunes de 18:00 a 20:00 del grupo CD-C está fuera de la disponibilidad del docente.', 'quitar: hecho'],
    ])
        ->and($asignado xor ! $franjaVigente)->toBeTrue();
});
