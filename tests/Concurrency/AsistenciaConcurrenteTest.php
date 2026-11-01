<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->usuario = User::factory()->create();
    $this->clase = CarbonImmutable::today('America/Lima')->subWeek();
    $periodo = Periodo::factory()->create([
        'anio' => $this->clase->year, 'matricula_inicio' => $this->clase->subDays(20), 'matricula_fin' => $this->clase->subDays(10),
        'fecha_inicio' => $this->clase->subDays(7), 'fecha_fin' => $this->clase->addDays(30),
    ]);
    $ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $this->grupo = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => 'AC-A', 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->create(['grupo_id' => $this->grupo->id, 'dia_semana' => $this->clase->dayOfWeekIso, 'hora_inicio' => '18:00:00', 'hora_fin' => '20:00:00']);
    [$this->actual, $this->nuevo] = Docente::factory()->count(2)->create()->all();
    foreach ([$this->actual, $this->nuevo] as $docente) {
        $docente->idiomas()->create(['idioma_id' => $ciclo->idioma_id, 'nivel_maximo' => NivelCurricular::Basico]);
        Disponibilidad::query()->create(['docente_id' => $docente->id, 'dia_semana' => $this->clase->dayOfWeekIso, 'hora_inicio' => '16:00:00', 'hora_fin' => '22:00:00']);
    }
    CargaAcademica::query()->create(['grupo_id' => $this->grupo->id, 'docente_id' => $this->actual->id, 'asignado_por' => $this->usuario->id]);
    $this->matricula = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'matriculado_en' => $this->clase->subDays(15)]);
});

it('refuses the roll call of a teacher whose group is being reassigned at the same time', function (): void {
    $estados = json_encode([$this->matricula->id => 'presente']);

    $salidas = enParalelo('reasignar-o-registrar-asistencia.php', [
        ['reasignar', $this->grupo->id, $this->nuevo->id, $this->usuario->id, $this->clase->toDateString(), '{}'],
        ['registrar', $this->grupo->id, $this->actual->id, $this->usuario->id, $this->clase->toDateString(), $estados],
    ]);

    expect($salidas)->toBe(['reasignar: hecho', 'registrar: No tienes a cargo el grupo AC-A.'])
        ->and(ClaseDictada::query()->count())->toBe(0);
});

it('refuses the grades of a teacher whose group is being reassigned at the same time', function (): void {
    $componente = DB::transaction(fn (): ComponenteEvaluacion => ComponenteEvaluacion::factory()->for(EsquemaEvaluacion::factory()->create(['vigente_desde' => $this->clase->subYear()]), 'esquema')->create(['peso' => '100.00']));
    $notas = json_encode([$this->matricula->id => [$componente->id => '15']]);

    $salidas = enParalelo('reasignar-o-registrar-asistencia.php', [
        ['reasignar', $this->grupo->id, $this->nuevo->id, $this->usuario->id, $this->clase->toDateString(), '{}'],
        ['notas', $this->grupo->id, $this->actual->id, $this->usuario->id, $this->clase->toDateString(), $notas],
    ]);

    expect($salidas)->toBe(['notas: No tienes a cargo el grupo AC-A.', 'reasignar: hecho'])
        ->and(NotaParcial::query()->count())->toBe(0);
});
