<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\RegistrarAprobacionesSinActa;
use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Data\CicloDelRecord;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Modules\Matricula\Actions\CorregirResultadoDeClasificacion;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->ingles = ActasFinales::planDeEstudios('Inglés', [4, 4, 4]);
    $this->persona = Persona::factory()->create();
    $this->coordinador = User::factory()->create()->givePermissionTo(PermisoClasificacion::Evaluar->value);
});

function cicloDeIngles(int $numero): Ciclo
{
    return Ciclo::query()->where(['idioma_id' => test()->ingles->id, 'numero' => $numero])->sole();
}

function clasificarEnCiclo(int $numero): SolicitudClasificacion
{
    $solicitud = SolicitudClasificacion::factory()->create(['persona_id' => test()->persona->id, 'idioma_id' => test()->ingles->id, 'pago_id' => null, 'created_at' => now()->subDay()]);

    return app(RegistrarResultadoDeClasificacion::class)(
        $solicitud->id,
        new ResultadoDeClasificacion(CarbonImmutable::parse('2026-10-24'), '70.00', cicloDeIngles($numero)->id, null),
        test()->coordinador,
    );
}

/**
 * @return list<array{0: int, 1: string, 2: ?string, 3: string, 4: ?int, 5: ?int}>
 */
function resumenDelRecord(int $personaId): array
{
    return array_map(
        fn (CicloDelRecord $ciclo): array => [$ciclo->resultado->cicloNumero, $ciclo->resultado->forma->value, $ciclo->resultado->promedio, $ciclo->resultado->estadoFinal->value, $ciclo->resultado->anio, $ciclo->resultado->mes],
        app(RecordDeNotas::class)($personaId)[0]->ciclos,
    );
}

it('records the cycles before the assigned placement cycle as passed by placement, without a grade, and shows them in the record', function (): void {
    $solicitud = clasificarEnCiclo(3);

    expect(DB::table('cer_aprobaciones')->orderBy('ciclo_numero')->get(['persona_id', 'idioma', 'nivel', 'ciclo_numero', 'ciclo_nombre', 'horas_lectivas', 'forma', 'nota', 'fecha', 'origen_tipo', 'origen_id', 'registrado_por'])->map(fn (object $fila): array => (array) $fila)->all())
        ->toBe([
            ['persona_id' => $this->persona->id, 'idioma' => 'Inglés', 'nivel' => 'basico', 'ciclo_numero' => 1, 'ciclo_nombre' => 'Básico 1', 'horas_lectivas' => null, 'forma' => 'clasificacion', 'nota' => null, 'fecha' => '2026-10-24', 'origen_tipo' => 'solicitud_clasificacion', 'origen_id' => $solicitud->id, 'registrado_por' => $this->coordinador->id],
            ['persona_id' => $this->persona->id, 'idioma' => 'Inglés', 'nivel' => 'basico', 'ciclo_numero' => 2, 'ciclo_nombre' => 'Básico 2', 'horas_lectivas' => null, 'forma' => 'clasificacion', 'nota' => null, 'fecha' => '2026-10-24', 'origen_tipo' => 'solicitud_clasificacion', 'origen_id' => $solicitud->id, 'registrado_por' => $this->coordinador->id],
        ])
        ->and(resumenDelRecord($this->persona->id))->toBe([[1, 'clasificacion', null, 'aprobado', 2026, 10], [2, 'clasificacion', null, 'aprobado', 2026, 10]]);
});

it('makes the level certificate available once the remaining cycles of the level are passed by acta, averaging only the graded cycles', function (): void {
    clasificarEnCiclo(3);
    $this->travelTo(CarbonImmutable::parse('2027-01-10 10:00', 'America/Lima'));
    ActasFinales::historial($this->persona, 'Inglés', [[3, '14.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);

    $certificado = app(EmitirCertificadoDeNivel::class)($this->persona->id, 'Inglés', NivelCurricular::Basico, $emisor);

    expect($certificado->nota_final)->toBe('15.00')
        ->and($certificado->horas_lectivas)->toBe(120)
        ->and(array_map(fn (array $ciclo): array => [$ciclo['ciclo_numero'], $ciclo['forma'], $ciclo['promedio']], $certificado->detalle['ciclos']))
        ->toBe([[1, 'clasificacion', null], [2, 'clasificacion', null], [3, 'matricula', '14.00'], [4, 'matricula', '16.00']]);
});

it('records each cycle only once when the same approvals are recorded twice', function (): void {
    $registrar = fn (): int => app(RegistrarAprobacionesSinActa::class)(
        new AprobacionSinActa($this->persona->id, 'Inglés', FormaDeAprobacion::Clasificacion, null, CarbonImmutable::parse('2026-10-24'), 'solicitud_clasificacion', 99, null),
        [new CicloAprobadoSinActa(NivelCurricular::Basico, 1, 'Básico 1', null), new CicloAprobadoSinActa(NivelCurricular::Basico, 2, 'Básico 2', null)],
    );

    expect($registrar())->toBe(2)
        ->and($registrar())->toBe(0)
        ->and(DB::table('cer_aprobaciones')->count())->toBe(2);
});

it('rejects changing or deleting an approval at the database', function (string $sentencia): void {
    clasificarEnCiclo(2);

    expect(fn () => DB::statement($sentencia))->toThrow(QueryException::class, 'cer_aprobaciones');
})->with([
    'update' => ["UPDATE cer_aprobaciones SET nota = '20.00'"],
    'delete' => ['DELETE FROM cer_aprobaciones'],
]);

it('adds the missing cycles when a correction raises the assigned cycle and keeps the recorded ones when it lowers it', function (): void {
    $solicitud = clasificarEnCiclo(2);
    $corregir = fn (int $numero) => app(CorregirResultadoDeClasificacion::class)(
        $solicitud->id,
        new ResultadoDeClasificacion(CarbonImmutable::parse('2026-10-24'), '75.00', cicloDeIngles($numero)->id, null),
        'Se revisó el examen escrito',
        $this->coordinador,
    );

    $corregir(4);
    $tras = DB::table('cer_aprobaciones')->orderBy('ciclo_numero')->pluck('ciclo_numero')->all();
    $corregir(2);

    expect($tras)->toBe([1, 2, 3])
        ->and(DB::table('cer_aprobaciones')->orderBy('ciclo_numero')->pluck('ciclo_numero')->all())->toBe([1, 2, 3]);
});

it('gives acta rows the form Matrícula and the year and month of their period start', function (): void {
    $periodo = Periodo::factory()->create(['anio' => 2026, 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-31', 'matricula_inicio' => '2026-07-20', 'matricula_fin' => '2026-07-31']);
    ActasFinales::historial($this->persona, 'Inglés', [[1, '13.00', 'aprobado']], periodoId: $periodo->id);

    $nota = HistorialDeNotas::de($this->persona->id, 'Inglés')[0];

    expect($nota)->forma->toBe(FormaDeAprobacion::Matricula)->anio->toBe(2026)->mes->toBe(8)
        ->and($nota->comoDetalle())->toMatchArray(['forma' => 'matricula', 'anio' => 2026, 'mes' => 8]);
});

it('lets a later acta of the same cycle win over the placement row', function (): void {
    clasificarEnCiclo(3);
    $this->travelTo(CarbonImmutable::parse('2027-01-10 10:00', 'America/Lima'));
    ActasFinales::historial($this->persona, 'Inglés', [[2, '17.00', 'aprobado']]);

    expect(resumenDelRecord($this->persona->id)[1])->toMatchArray([0 => 2, 1 => 'matricula', 2 => '17.00']);
});

it('keeps the record of a student without placement made only of their actas', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '14.00', 'aprobado'], [2, '09.00', 'desaprobado'], [2, '12.00', 'aprobado']]);

    expect(array_map(fn (array $fila): array => array_slice($fila, 0, 4), resumenDelRecord($this->persona->id)))
        ->toBe([[1, 'matricula', '14.00', 'aprobado'], [2, 'matricula', '12.00', 'aprobado']])
        ->and(app(RecordDeNotas::class)($this->persona->id)[0]->promedio)->toBe('13.00');
});
