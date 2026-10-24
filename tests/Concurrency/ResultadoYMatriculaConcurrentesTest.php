<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

afterEach(function (): void {
    $this->truncateTablesForAllConnections();
});

it('serializes a result registration with an enrollment of the same person', function (): void {
    $ingles = Idioma::factory()->create();
    $cicloVI = Ciclo::factory()->for($ingles)->create(['numero' => 6]);
    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->for($cicloVI)->create();
    $persona = Persona::factory()->create();
    $pagoMatricula = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $persona->id]);
    $solicitud = SolicitudClasificacion::factory()->create(['persona_id' => $persona->id, 'idioma_id' => $ingles->id, 'created_at' => now()->subDay()]);
    $evaluador = User::factory()->create();

    $salidas = enParalelo('resultado-o-matricula.php', [['r', $solicitud->id, $cicloVI->id, $evaluador->id], ['m', $persona->id, $grupo->id, $pagoMatricula->id]]);
    $matriculado = Matricula::query()->where('persona_id', $persona->id)->exists();

    expect($salidas)->toContain('registrado')
        ->and(Habilitacion::query()->where('persona_id', $persona->id)->value('ciclo_id'))->toBe($cicloVI->id)
        ->and($salidas)->toBe($matriculado ? ['matriculada', 'registrado'] : ['No está habilitado para este ciclo: debe aprobar el ciclo anterior o rendir el examen de clasificación.', 'registrado']);
})->with([1, 2, 3]);
