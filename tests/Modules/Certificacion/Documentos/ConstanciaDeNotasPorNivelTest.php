<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Programa;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeNotasDelActa;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;
use Tests\Support\DatosDelTablero;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::planDeEstudios('Italiano', [4, 4, 4]);
    $setiembre = DatosDelTablero::periodo('2026-IX', '2026-09-01', '2026-09-30', 2026, 9);
    $octubre = DatosDelTablero::periodo('2026-X', '2026-10-01', '2026-10-31', 2026, 10);
    ActasFinales::historial($this->ana, 'Italiano', [[1, '15.00', 'aprobado'], [2, '09.00', 'desaprobado']], periodoId: $setiembre->id);
    ActasFinales::historial($this->ana, 'Italiano', [[2, '16.50', 'aprobado'], [5, '17.00', 'aprobado']], periodoId: $octubre->id);
});

function constanciaV2(NivelCurricular $nivel = NivelCurricular::Basico): Documento
{
    return app(EmitirConstanciaDeNotas::class)(test()->ana->id, 'Italiano', $nivel, test()->emisor);
}

function htmlDelDocumento(Documento $documento): string
{
    $pdf = app(GenerarDocumentoPdf::class)($documento->id);

    return view($pdf->viewName, [...$pdf->viewData, 'qr' => CodigoQr::comoDataUri('x')])->render();
}

it('issues a grade record of one level with its passed cycles, year, month and form of approval, and the student data', function (): void {
    DB::table('cer_aprobaciones')->insert([
        'persona_id' => $this->ana->id, 'idioma' => 'Italiano', 'nivel' => 'basico', 'ciclo_numero' => 3, 'ciclo_nombre' => 'Básico 3',
        'horas_lectivas' => 60, 'forma' => 'suficiencia', 'nota' => '14.00', 'fecha' => '2026-11-05', 'origen_tipo' => 'prueba', 'origen_id' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $ultimoGrupo = Acta::query()->where('ciclo_numero', 5)->value('grupo_id');
    DB::table('aca_grupos')->where('id', $ultimoGrupo)->update(['programa_id' => Programa::factory()->create(['nombre' => 'Programa Sintético'])->id]);

    $documento = constanciaV2();

    expect($documento->nivel)->toBe(NivelCurricular::Basico)
        ->and($documento->detalle['version'])->toBe(2)
        ->and($documento->detalle['dni'])->toBe('70000001')
        ->and($documento->detalle['programa'])->toBe('Programa Sintético')
        ->and($documento->detalle['curriculo'])->toBe('01')
        ->and($documento->detalle['filas'])->toBe([
            ['item' => 1, 'nivel' => 'Básico', 'ciclo' => 'I', 'anio' => 2026, 'mes' => 'Setiembre', 'nota' => '15.00', 'forma' => 'MATRÍCULA'],
            ['item' => 2, 'nivel' => 'Básico', 'ciclo' => 'II', 'anio' => 2026, 'mes' => 'Octubre', 'nota' => '16.50', 'forma' => 'MATRÍCULA'],
            ['item' => 3, 'nivel' => 'Básico', 'ciclo' => 'III', 'anio' => 2026, 'mes' => 'Noviembre', 'nota' => '14.00', 'forma' => 'SUFICIENCIA'],
        ])
        ->and($documento->detalle['programa_curricular'])->toBe(['basico' => 4, 'intermedio' => 4, 'avanzado' => 4])
        ->and(array_column($documento->detalle['notas'], 'ciclo_numero'))->toBe([1, 2, 3]);
});

it('issues the intermediate level apart, with only its cycles', function (): void {
    expect(array_column(constanciaV2(NivelCurricular::Intermedio)->detalle['filas'], 'ciclo'))->toBe(['V']);
});

it('refuses a level with no passed cycle', function (): void {
    expect(fn (): Documento => constanciaV2(NivelCurricular::Avanzado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La persona no tiene ciclos aprobados de Italiano en el nivel Avanzado.');
});

it('takes the program by default when the level has no acta', function (): void {
    $beto = Persona::factory()->create();
    DB::table('cer_aprobaciones')->insert([
        'persona_id' => $beto->id, 'idioma' => 'Italiano', 'nivel' => 'basico', 'ciclo_numero' => 1, 'ciclo_nombre' => 'Básico 1',
        'horas_lectivas' => null, 'forma' => 'clasificacion', 'nota' => null, 'fecha' => '2026-11-05', 'origen_tipo' => 'prueba', 'origen_id' => 2,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $documento = app(EmitirConstanciaDeNotas::class)($beto->id, 'Italiano', NivelCurricular::Basico, $this->emisor);

    expect($documento->detalle['programa'])->toBe('Jóvenes y Adultos')
        ->and($documento->detalle['filas'][0])->toMatchArray(['nota' => '—', 'forma' => 'CLASIFICACIÓN', 'mes' => 'Noviembre']);
});

it('prints the real format: header, opening, student data, table, curricular program, place and date, signature and QR', function (): void {
    $documento = constanciaV2();
    $html = htmlDelDocumento($documento);

    expect($html)->toContain('UNIVERSIDAD NACIONAL SANTIAGO ANTÚNEZ DE MAYOLO')
        ->toContain('Una Nueva Universidad para el Desarrollo')
        ->toContain('EL DIRECTOR DEL CENTRO DE IDIOMAS Y LENGUAS NATIVAS DE LA UNIVERSIDAD NACIONAL &quot;SANTIAGO ANTÚNEZ DE MAYOLO&quot; QUIEN SUSCRIBE AL FINAL, HACE CONSTAR LO SIGUIENTE:')
        ->toContain('CONSTANCIA DE NOTAS N° 2026 - 00001')
        ->toContain('ALVA PRUEBA, ANA')
        ->toContain('ITALIANO (JÓVENES Y ADULTOS)')
        ->toContain('70000001')
        ->toContain('FORMA DE APROBACIÓN')
        ->toContain('Setiembre')
        ->toContain('MATRÍCULA')
        ->toContain('Programa Curricular')
        ->toContain('4 Ciclos')
        ->toContain('Huaraz, 16 de Noviembre de 2026')
        ->toContain('Prolongación Simón Bolívar S/N – Huaraz – Telf: 043-640020 / Anexo 3105')
        ->toContain('Web: cid.unasam.edu.pe / Facebook: cidunasam')
        ->toContain("Código único: {$documento->codigo}")
        ->not->toContain('N.º 000001-2026');
});

it('keeps the yearly number of other documents in their current format', function (): void {
    constanciaV2();
    $matricula = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaMatricula, $this->ana->id, ['idioma' => 'Italiano', 'detalle' => []], $this->emisor);

    expect($matricula->refresh()->numeroOficial())->toBe('N.º 000001-2026')
        ->and(constanciaV2(NivelCurricular::Intermedio)->numeroOficial())->toBe('N° 2026 - 00002');
});

it('still renders a grade record issued before the per level format with its own template', function (): void {
    $anterior = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaNotas, $this->ana->id, [
        'idioma' => 'Italiano',
        'detalle' => ['notas' => [[
            'ciclo_numero' => 1, 'ciclo_nombre' => 'Básico 1', 'nivel' => 'basico', 'periodo' => '2026-IX', 'horas_lectivas' => 60,
            'promedio' => '15.00', 'estado_final' => 'aprobado',
        ]]],
    ], $this->emisor);

    expect(htmlDelDocumento($anterior))->toContain('LA DIRECCIÓN DEL CENTRO DE IDIOMAS Y LENGUAS NATIVAS')
        ->toContain('Básico 1 (1)')
        ->toContain('N.º 000001-2026');
});

it('issues the grade records of an acta for its level only', function (): void {
    $acta = Acta::query()->where('ciclo_numero', 5)->sole();

    $resumen = app(EmitirConstanciasDeNotasDelActa::class)($acta->id, $this->emisor);

    expect($resumen->emitidos)->toHaveCount(1)
        ->and($resumen->emitidos[0]->nivel)->toBe(NivelCurricular::Intermedio)
        ->and(array_column($resumen->emitidos[0]->detalle['filas'], 'ciclo'))->toBe(['V']);
});

it('still issues the whole-language record for a trámite requested before records were per level', function (): void {
    $emitido = app(DocumentoParaTramite::class)(TipoDocumento::ConstanciaNotas, $this->ana->id, ['idioma' => 'Italiano'], $this->emisor);
    $documento = Documento::query()->findOrFail($emitido->id);

    expect($documento->detalle)->not->toHaveKey('version')
        ->and(array_column($documento->detalle['notas'], 'ciclo_numero'))->toBe([1, 2, 2, 5])
        ->and($documento->numeroOficial())->toBe('N.º 000001-2026')
        ->and(htmlDelDocumento($documento))->toContain('CONSTANCIA DE NOTAS');
});
