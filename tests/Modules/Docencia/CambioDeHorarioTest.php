<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

beforeEach(function (): void {
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $this->grupo = Grupo::factory()->create(['codigo' => 'HC-A', 'periodo_id' => $periodo->id, 'ciclo_id' => $ciclo->id, 'aula_id' => null, 'estado' => EstadoGrupo::Abierto]);
    $this->otro = Grupo::factory()->create(['codigo' => 'HC-B', 'periodo_id' => $periodo->id, 'ciclo_id' => $ciclo->id, 'aula_id' => null, 'estado' => EstadoGrupo::Abierto]);
    $this->sesion = Horario::factory()->create(['grupo_id' => $this->grupo->id, 'dia_semana' => 1, 'hora_inicio' => '08:00:00', 'hora_fin' => '10:00:00']);
    Horario::factory()->create(['grupo_id' => $this->otro->id, 'dia_semana' => 1, 'hora_inicio' => '10:00:00', 'hora_fin' => '12:00:00']);
    $this->docente = Docente::factory()->create();
    $this->docente->idiomas()->create(['idioma_id' => $ciclo->idioma_id, 'nivel_maximo' => NivelCurricular::Basico]);
    Disponibilidad::query()->create(['docente_id' => $this->docente->id, 'dia_semana' => 1, 'hora_inicio' => '07:00:00', 'hora_fin' => '13:00:00']);
    $usuario = User::factory()->create();
    app(AsignarDocente::class)($this->grupo->id, $this->docente->id, $usuario);
    app(AsignarDocente::class)($this->otro->id, $this->docente->id, $usuario);
});

function sesionesDe(Grupo $grupo): array
{
    return Horario::query()->where('grupo_id', $grupo->id)->orderBy('dia_semana')->orderBy('hora_inicio')->get()
        ->map(fn (Horario $horario): array => [$horario->dia_semana, $horario->hora_inicio, $horario->hora_fin])->all();
}

it('refuses a schedule change that leaves the assigned teacher with a clash or outside the availability', function (int $dia, string $inicio, string $fin, ?bool $editar, string $mensaje): void {
    $antes = sesionesDe($this->grupo);

    expect(fn () => app(GuardarHorario::class)($this->grupo->id, $dia, $inicio, $fin, $editar ? $this->sesion->id : null))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(sesionesDe($this->grupo))->toBe($antes);
})->with([
    'edit into the other group' => [1, '09:00', '11:00', true, 'ya dicta el grupo HC-B el Lunes de 10:00 a 12:00, que se cruza con el grupo HC-A.'],
    'add a session outside the availability' => [2, '08:00', '10:00', false, 'La sesión del Martes de 08:00 a 10:00 del grupo HC-A está fuera de la disponibilidad del docente.'],
    'stretch past the window' => [1, '06:00', '08:00', false, 'La sesión del Lunes de 06:00 a 08:00 del grupo HC-A está fuera de la disponibilidad del docente.'],
]);

it('accepts a schedule change that the assigned teacher can still teach', function (): void {
    app(GuardarHorario::class)($this->grupo->id, 1, '07:00', '09:30', $this->sesion->id);

    expect(sesionesDe($this->grupo))->toBe([[1, '07:00:00', '09:30:00']]);
});

it('keeps changing the schedule of a group without a teacher as before', function (): void {
    $libre = Grupo::factory()->create(['codigo' => 'HC-L', 'aula_id' => null, 'estado' => EstadoGrupo::Abierto]);

    app(GuardarHorario::class)($libre->id, 2, '08:00', '10:00');

    expect(sesionesDe($libre))->toBe([[2, '08:00:00', '10:00:00']]);
});
