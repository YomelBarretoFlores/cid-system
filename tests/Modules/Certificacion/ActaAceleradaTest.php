<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\PrepararActaDelGrupo;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
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

it('records both cycles with the one grade and enables the cycle after the second for who passed', function (): void {
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
