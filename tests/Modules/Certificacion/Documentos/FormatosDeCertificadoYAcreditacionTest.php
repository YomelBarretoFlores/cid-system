<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeAcreditacion;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Actions\GuardarDatosInstitucionales;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Models\Firmante;
use App\Modules\Certificacion\Models\Institucion;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\FilasDeLaConstancia;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo([PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '17.00', 'aprobado'], [2, '18.00', 'aprobado'], [3, '18.50', 'aprobado'], [4, '17.50', 'aprobado']]);
});

function htmlDelFormato(Documento $documento): string
{
    $pdf = app(GenerarDocumentoPdf::class)($documento->id);

    return view($pdf->viewName, [...$pdf->viewData, 'qr' => CodigoQr::comoDataUri('x')])->render();
}

function certificadoDeAna(): Documento
{
    return app(EmitirCertificadoDeNivel::class)(test()->ana->id, 'Inglés', NivelCurricular::Basico, test()->emisor);
}

/**
 * @return array<string, string>
 */
function datosInstitucionalesDePrueba(array $cambios = []): array
{
    return [
        ...Institucion::query()->findOrFail(Institucion::FILA)->only([
            'universidad', 'lema', 'centro', 'direccion', 'telefono', 'web', 'facebook', 'ciudad', 'formato_numero',
            'formato_numero_constancia_notas', 'resolucion_creacion', 'base_legal_acreditacion',
        ]),
        ...$cambios,
    ];
}

function acreditacionDeAna(string $mencion = 'Doctorado en Administración'): Documento
{
    return app(EmitirConstanciaDeAcreditacion::class)(test()->ana->id, 'Inglés', NivelCurricular::Basico, $mencion, test()->emisor);
}

it('prints no seal placeholder on any document, since they are stamped by hand', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $curso = ActasFinales::curso();
    $matricula = ActasFinales::estudiante($curso['grupo'], 'Quispe');
    ActasFinales::preActa($matricula, [$curso['parcial']->id => '15.00', $curso['final']->id => '14.00', $curso['oral']->id => '16.00']);
    app(CerrarActa::class)($curso['grupo']->id, $curso['docente']->id, $curso['usuario']);
    $antigua = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaNotas, $this->ana->id, [
        'idioma' => 'Inglés', 'detalle' => ['notas' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), HistorialDeNotas::de($this->ana->id, 'Inglés'))],
    ], $this->emisor);
    $documentos = [
        certificadoDeAna(), acreditacionDeAna(), $antigua,
        app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor),
        app(EmitirConstanciaDeMatricula::class)($matricula->id, $this->emisor),
    ];
    $acta = app(GenerarActaFinalPdf::class)((int) Acta::query()->where('grupo_id', $curso['grupo']->id)->value('id'));

    foreach ($documentos as $documento) {
        expect(htmlDelFormato($documento))->not->toContain('Sello');
    }

    expect(view($acta->viewName, $acta->viewData)->render())->not->toContain('Sello');
});

it('signs the certificate with the Vicerrector on the left and the Director on the right, in their configured order', function (): void {
    Firmante::query()->create(['cargo' => 'Director del Centro de Idiomas', 'nombre' => 'Dr. Mario Huerta Ríos', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => false, 'orden' => 2, 'vigente_desde' => '2026-01-01']);
    Firmante::query()->create(['cargo' => 'Vicerrector Académico', 'nombre' => 'Dr. Pedro Sintético Ruiz', 'firma_certificados' => true, 'firma_constancias' => false, 'firma_actas' => false, 'orden' => 1, 'vigente_desde' => '2026-01-01']);

    $html = htmlDelFormato(certificadoDeAna());

    expect($html)->toContain('Dr. Pedro Sintético Ruiz', 'Vicerrector Académico', 'Dr. Mario Huerta Ríos', 'Director del Centro de Idiomas')
        ->and(strpos($html, 'Vicerrector Académico'))->toBeLessThan(strpos($html, 'Director del Centro de Idiomas'));
});

it('leaves the Vicerrector and Director lines with only their positions when no certificate signer is configured', function (): void {
    $html = htmlDelFormato(certificadoDeAna());

    expect($html)->toContain('Vicerrector Académico', 'Director del Centro de Idiomas')
        ->and(strpos($html, 'Vicerrector Académico'))->toBeLessThan(strpos($html, 'Director del Centro de Idiomas'));
});

it('prints the real certificate wording: Vicerrectoría, center, creation resolution, hours and registry number', function (): void {
    $html = htmlDelFormato(certificadoDeAna());

    expect($html)->toContain(
        'Vicerrectoría Académica', 'CENTRO DE IDIOMAS Y LENGUAS NATIVAS', 'Creado por R.R. N° 860-80-UNASAM, del 5 de Setiembre de 1980',
        'CERTIFICADO', 'Otorgado a:', 'Alva Prueba, Ana',
        'Por haber aprobado satisfactoriamente el nivel <strong>BÁSICO</strong> del idioma <strong>INGLÉS</strong> desarrollado en el Centro de Idiomas de la UNASAM; haciendo un total de <strong>240 horas académicas</strong>.',
        'N° Registro: <strong style="color: #1d1b16;">000001-2026</strong>', 'Huaraz, 16 de noviembre de 2026', 'Código único:',
    )->not->toContain('de culminación de nivel', 'promedio final');
});

it('prints the creation resolution saved in the institutional data', function (): void {
    app(GuardarDatosInstitucionales::class)(datosInstitucionalesDePrueba(['resolucion_creacion' => 'Creado por R.R. N° 001-2026-PRUEBA']), $this->emisor);

    expect(htmlDelFormato(certificadoDeAna()))->toContain('Creado por R.R. N° 001-2026-PRUEBA');
});

it('puts the student photo on the certificate only when the person has one', function (): void {
    Storage::fake('local');
    $sinFoto = htmlDelFormato(certificadoDeAna());
    Storage::disk('local')->put('fotos/ana.png', UploadedFile::fake()->image('ana.png', 300, 400)->getContent());
    $this->ana->update(['foto' => 'fotos/ana.png']);
    $certificado = Documento::query()->where('tipo', TipoDocumento::CertificadoNivel)->sole();

    expect($sinFoto)->not->toContain('alt="Fotografía"')
        ->and(htmlDelFormato($certificado))->toContain('alt="Fotografía" src="data:image/png;base64,');
});

it('prints the accreditation in the real format with grade in words, modality, completion date, validity and legal basis', function (): void {
    $documento = acreditacionDeAna();
    $html = htmlDelFormato($documento);

    expect($documento->detalle['version'])->toBe(3)
        ->and($html)->toContain(
            'ACREDITACIÓN DE CONOCIMIENTO DE IDIOMA EXTRANJERO',
            '(De conformidad con la Normatividad legal. Ley 30220, Art. 43 y Reglamento de Suficiencia de Idioma Extranjero)',
            'Otorgado a', 'ALVA PRUEBA ANA', 'En el Idioma', 'Inglés', 'Nivel', 'Básico',
            'Calificativo', 'Dieciocho (18)', 'Modalidad', 'Programa de Enseñanza de Idioma Extranjero', 'Fecha', '16 de Noviembre del 2026',
            'Así consta en el Acta Final correspondiente, disponible para verificación en caso de estimarse necesario.',
            'Válido para obtener el Grado de Doctor en Administración.',
            'Huaraz, 16 de Noviembre de 2026.', 'N° Registro: <strong>2026 - 00001</strong>', $documento->codigo,
        )->not->toContain('Sello');
});

it('writes the master degree and the proficiency exam modality when the level was passed by proficiency', function (): void {
    $beto = Persona::factory()->create();
    foreach ([5, 6, 7, 8] as $ciclo) {
        DB::table('cer_aprobaciones')->insert([
            'persona_id' => $beto->id, 'idioma' => 'Inglés', 'nivel' => 'intermedio', 'ciclo_numero' => $ciclo, 'ciclo_nombre' => "Intermedio {$ciclo}",
            'horas_lectivas' => 60, 'forma' => 'suficiencia', 'nota' => '15.00', 'fecha' => '2026-05-29', 'origen_tipo' => 'prueba', 'origen_id' => $ciclo,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $html = htmlDelFormato(app(EmitirConstanciaDeAcreditacion::class)($beto->id, 'Inglés', NivelCurricular::Intermedio, 'Maestría en Educación Superior', $this->emisor));

    expect($html)->toContain('Examen de Suficiencia', 'Quince (15)', '29 de Mayo del 2026', 'Válido para obtener el Grado de Maestro en Educación Superior.');
});

it('prints the legal basis saved in the institutional data', function (): void {
    app(GuardarDatosInstitucionales::class)(datosInstitucionalesDePrueba(['base_legal_acreditacion' => '(Base legal sintética de prueba)']), $this->emisor);

    expect(htmlDelFormato(acreditacionDeAna()))->toContain('(Base legal sintética de prueba)');
});

it('keeps rendering an accreditation issued before the real format with its first template', function (): void {
    $historial = HistorialDeNotas::de($this->ana->id, 'Inglés');
    $notas = FilasDeLaConstancia::aprobadasDelNivel($historial, NivelCurricular::Basico);
    $filas = app(FilasDeLaConstancia::class);
    $anterior = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaAcreditacion, $this->ana->id, [
        'idioma' => 'Inglés', 'nivel' => 'basico',
        'detalle' => [
            'version' => 2, ...$filas->datosDelEstudiante($this->ana->id, 'Inglés', $historial), 'mencion' => 'Maestría en Educación',
            'notas' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), $notas),
            'filas' => FilasDeLaConstancia::filas($notas), 'programa_curricular' => $filas->programaCurricular('Inglés'),
        ],
    ], $this->emisor);

    expect(htmlDelFormato($anterior))->toContain('CONSTANCIA DE ACREDITACIÓN N° 2026 - 00001', 'MENCIÓN', 'MAESTRÍA EN EDUCACIÓN', 'Programa Curricular')
        ->not->toContain('ACREDITACIÓN DE CONOCIMIENTO DE IDIOMA EXTRANJERO');
});
