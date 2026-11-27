<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\PrepararActaDelGrupo;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $idiomaId = $this->grupo->ciclo->idioma_id;
    $this->cicloDos = Ciclo::factory()->create(['idioma_id' => $idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico II', 'horas_lectivas' => 40]);
    $this->cicloTres = Ciclo::factory()->create(['idioma_id' => $idiomaId, 'numero' => 3, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico III']);
    $this->grupo->update(['es_acelerado' => true, 'ciclo_hasta_id' => $this->cicloDos->id]);
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo');
    [$oral, $parcial, $final] = [$this->curso['oral']->id, $this->curso['parcial']->id, $this->curso['final']->id];
    ActasFinales::preActa($this->ana, [$oral => '15.00', $parcial => '15.00', $final => '15.00']);
    ActasFinales::preActa($this->beto, [$oral => '8.00', $parcial => '8.00', $final => '8.00']);
});

function cerrarActaAcelerada(): void
{
    app(CerrarActa::class)(test()->grupo->id, test()->curso['docente']->id, test()->curso['usuario']);
}

it('closes one acta for both cycles with the second cycle and the hours of both', function (): void {
    expect(app(PrepararActaDelGrupo::class)($this->grupo->id))
        ->cicloNumeroHasta->toBe(2)
        ->cicloNombreHasta->toBe('Básico II');

    cerrarActaAcelerada();

    expect(Acta::query()->sole())
        ->ciclo_numero->toBe(1)
        ->ciclo_nombre->toBe('Básico I')
        ->ciclo_numero_hasta->toBe(2)
        ->ciclo_nombre_hasta->toBe('Básico II')
        ->horas_lectivas->toBe(100)
        ->horas_lectivas_hasta->toBe(40);
});

it('records both cycles with the one grade when the scheme has no second cycle grade, and enables the cycle after the second for who passed', function (): void {
    cerrarActaAcelerada();

    $resumen = fn (Persona|int $persona): array => array_map(
        fn (CicloDelRecord $ciclo): array => [$ciclo->resultado->cicloNumero, $ciclo->resultado->cicloNombre, $ciclo->resultado->horasLectivas, $ciclo->resultado->promedio, $ciclo->resultado->estadoFinal->value],
        app(RecordDeNotas::class)(is_int($persona) ? $persona : $persona->id)[0]->ciclos,
    );

    expect($resumen($this->ana->persona_id))->toBe([[1, 'Básico I', 60, '15.00', 'aprobado'], [2, 'Básico II', 40, '15.00', 'aprobado']])
        ->and($resumen($this->beto->persona_id))->toBe([[1, 'Básico I', 60, '8.00', 'desaprobado'], [2, 'Básico II', 40, '8.00', 'desaprobado']])
        ->and(Habilitacion::query()->get(['persona_id', 'ciclo_id'])->map->only(['persona_id', 'ciclo_id'])->all())
        ->toBe([['persona_id' => $this->ana->persona_id, 'ciclo_id' => $this->cicloTres->id]]);
});

it('enables the cycle after the second even when the second cycle was deactivated after the group was created', function (): void {
    // The cycle order keeps inactive cycles; the pair sits after other cycles so a missed lookup could not pass by chance.
    $this->cicloTres->update(['numero' => 5]);
    $this->cicloDos->update(['numero' => 4, 'activo' => false]);
    $this->grupo->ciclo->update(['numero' => 3]);
    $idiomaId = $this->grupo->ciclo->idioma_id;
    Ciclo::factory()->create(['idioma_id' => $idiomaId, 'numero' => 1, 'nivel' => NivelCurricular::Basico]);
    Ciclo::factory()->create(['idioma_id' => $idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico]);

    cerrarActaAcelerada();

    expect(Habilitacion::query()->get(['persona_id', 'ciclo_id'])->map->only(['persona_id', 'ciclo_id'])->all())
        ->toBe([['persona_id' => $this->ana->persona_id, 'ciclo_id' => $this->cicloTres->id]]);
});

it('certifies a level completed through a mix of regular and accelerated groups', function (): void {
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $eva = Persona::factory()->create();
    ActasFinales::historial($eva, 'Francés', [[1, '14.00', 'aprobado', 2], [3, '16.00', 'aprobado'], [4, '17.00', 'aprobado']]);

    expect(app(NivelesPorCertificar::class)($eva->id))->toEqual([new NivelPorCertificar('Francés', NivelCurricular::Basico)]);

    $certificado = app(EmitirCertificadoDeNivel::class)($eva->id, 'Francés', NivelCurricular::Basico, $emisor);

    expect($certificado)
        ->horas_lectivas->toBe(240)
        ->nota_final->toBe('15.25')
        ->and(array_column($certificado->detalle['ciclos'], 'ciclo_numero'))->toBe([1, 2, 3, 4]);
});

it('does not certify the level while a cycle after the accelerated pair is missing', function (): void {
    $eva = Persona::factory()->create();
    ActasFinales::historial($eva, 'Francés', [[1, '14.00', 'aprobado', 2], [3, '16.00', 'aprobado']]);

    expect(app(NivelesPorCertificar::class)($eva->id))->toBe([]);
});

/**
 * The scheme of the course, for the accelerated regime, with a second entered column whose rounded value is the grade of the second cycle.
 */
function segundoCicloConSuNota(): ComponenteEvaluacion
{
    $esquema = EsquemaEvaluacion::query()->sole();
    $esquema->update(['regimen' => 'acelerado']);
    $segunda = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral_2', 'nombre' => 'Expresión oral II', 'orden' => 7]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral_2'], redondeo: 'entero', ciclo: 2)->create(['codigo' => 'nota_ciclo_2', 'nombre' => 'Nota del segundo ciclo', 'orden' => 8]);

    return $segunda;
}

it('gives each cycle of an accelerated acta its own grade and state when the scheme grades the second cycle apart', function (): void {
    $segunda = segundoCicloConSuNota();
    $carla = ActasFinales::estudiante($this->grupo, 'Castro');
    [$oral, $parcial, $final] = [$this->curso['oral']->id, $this->curso['parcial']->id, $this->curso['final']->id];
    ActasFinales::preActa($this->ana, [$segunda->id => '16.40']);
    ActasFinales::preActa($this->beto, [$segunda->id => '14.00']);
    ActasFinales::preActa($carla, [$oral => '15.00', $parcial => '15.00', $final => '15.00', $segunda->id => '9.00']);

    cerrarActaAcelerada();

    expect(NotaFinal::query()->orderBy('estudiante_nombre')->get()->map(fn (NotaFinal $nota): array => [$nota->promedio, $nota->estado_final->value, $nota->promedio_hasta, $nota->estado_final_hasta?->value])->all())->toBe([
        ['15.00', 'aprobado', '16.00', 'aprobado'],
        ['8.00', 'desaprobado', '14.00', 'aprobado'],
        ['15.00', 'aprobado', '9.00', 'desaprobado'],
    ])
        ->and(array_map(fn (CicloDelRecord $ciclo): array => [$ciclo->resultado->cicloNumero, $ciclo->resultado->promedio, $ciclo->resultado->estadoFinal->value], app(RecordDeNotas::class)($this->ana->persona_id)[0]->ciclos))
        ->toBe([[1, '15.00', 'aprobado'], [2, '16.00', 'aprobado']])
        ->and(Habilitacion::query()->orderBy('persona_id')->get(['persona_id', 'ciclo_id'])->map->only(['persona_id', 'ciclo_id'])->all())->toBe([
            ['persona_id' => $this->ana->persona_id, 'ciclo_id' => $this->cicloTres->id],
            ['persona_id' => $carla->persona_id, 'ciclo_id' => $this->cicloDos->id],
        ]);
});

it('shows the grade and state of each cycle in the acta of an accelerated group', function (): void {
    $segunda = segundoCicloConSuNota();
    ActasFinales::preActa($this->ana, [$segunda->id => '16.40']);

    $this->actingAs($this->curso['usuario'])
        ->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Expresión oral II', 'Promedio ciclo I', 'Estado ciclo I', 'Promedio ciclo II', 'Estado ciclo II', 'Alva Prueba, Ana', '15.00', 'Aprobado', '16.00', 'Aprobado']);
});

it('recalculates the second cycle when an accelerated acta is rectified', function (): void {
    $segunda = segundoCicloConSuNota();
    ActasFinales::preActa($this->ana, [$segunda->id => '16.40']);
    cerrarActaAcelerada();
    app(AutorizarRectificacion::class)(Acta::query()->sole()->id, 'Nota mal digitada', User::factory()->create()->givePermissionTo(PermisoCertificacion::valores()));

    app(RectificarNotasFinales::class)($this->grupo->id, $this->curso['docente']->id, [$this->ana->id => [$segunda->id => '10.00']]);

    expect(NotaFinal::query()->where('matricula_id', $this->ana->id)->sole())
        ->promedio->toBe('15.00')
        ->promedio_hasta->toBe('10.00')
        ->estado_final_hasta->value->toBe('desaprobado');
});

it('makes the database refuse a second cycle grade outside an accelerated acta, and require it in one', function (): void {
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Francés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado', 3]], 'abierta');
    [$regular, $acelerada] = NotaFinal::query()->where('persona_id', $persona->id)->orderBy('id')->get()->all();

    expect(fn () => DB::transaction(fn (): int => DB::table('cer_notas_finales')->where('id', $regular->id)->update(['promedio_hasta' => '14.00', 'estado_final_hasta' => 'aprobado'])))
        ->toThrow(QueryException::class, 'el segundo ciclo tiene nota solo en un acta acelerada')
        ->and(fn () => DB::transaction(fn (): int => DB::table('cer_notas_finales')->where('id', $acelerada->id)->update(['promedio_hasta' => null, 'estado_final_hasta' => null])))
        ->toThrow(QueryException::class, 'el segundo ciclo tiene nota solo en un acta acelerada');
});

it('gives the second cycle of an accelerated acta closed before this change the grade it shared with the first', function (): void {
    $migracion = require base_path('app/Modules/Certificacion/Database/Migrations/2026_12_05_100200_add_second_cycle_grade_to_cer_notas_finales.php');
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Francés', [[1, '14.00', 'aprobado', 2]]);
    $migracion->down();

    $migracion->up();

    expect(NotaFinal::query()->where('persona_id', $persona->id)->sole())
        ->promedio_hasta->toBe('14.00')
        ->estado_final_hasta->value->toBe('aprobado');
});
