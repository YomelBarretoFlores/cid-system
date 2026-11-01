<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Firmante;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 11:30', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.50', 'aprobado'], [3, '16.25', 'aprobado'], [4, '13.00', 'aprobado']]);
    Firmante::query()->create(['cargo' => 'Director del Centro de Idiomas y Lenguas Nativas', 'nombre' => 'Dr. Mario Huerta Ríos', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => false, 'orden' => 1, 'vigente_desde' => '2027-01-01']);
    Firmante::query()->create(['cargo' => 'Coordinador Académico', 'nombre' => 'Mg. Luis Paredes Vega', 'firma_certificados' => true, 'firma_constancias' => false, 'firma_actas' => true, 'orden' => 2, 'vigente_desde' => '2027-01-01']);
});

/**
 * @return string the rendered HTML of the PDF the builder would print
 */
function htmlDelPdf(PdfBuilder $pdf): string
{
    return view($pdf->viewName, $pdf->viewData)->render();
}

it('prints the level certificate in landscape, as a formal certificate with its signers, number, code and QR', function (): void {
    $certificado = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    $pdf = app(GenerarDocumentoPdf::class)($certificado->id);

    expect($pdf->orientation)->toBe('Landscape')
        ->and(htmlDelPdf($pdf))->toContain(
            'CERTIFICADO', 'Otorgado a', 'Alva Prueba, Ana', 'DNI 70000001', 'Básico', 'Inglés', '240 horas lectivas', '14.69',
            'Huaraz, 10 de abril de 2027', 'Dr. Mario Huerta Ríos', 'Director del Centro de Idiomas y Lenguas Nativas', 'Mg. Luis Paredes Vega', 'Coordinador Académico',
            'N.º 000001-2027', $certificado->codigo, 'data:image/png;base64,',
        );
});

it('prints a constancia as a formal letter with only its own signers', function (): void {
    $constancia = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);
    $html = htmlDelPdf(app(GenerarDocumentoPdf::class)($constancia->id));

    expect($html)->toContain('CONSTANCIA DE NOTAS', 'HACE CONSTAR', 'Alva Prueba, Ana', 'Se expide la presente', 'Huaraz, 10 de abril de 2027', 'Dr. Mario Huerta Ríos', 'N.º 000001-2027', $constancia->codigo)
        ->and($html)->not->toContain('Mg. Luis Paredes Vega');
});

it('leaves a signature line for the CILN direction when no signer is registered', function (): void {
    Firmante::query()->delete();
    $constancia = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);

    expect(htmlDelPdf(app(GenerarDocumentoPdf::class)($constancia->id)))->toContain('Dirección del Centro de Idiomas y Lenguas Nativas');
});

it('prints the acta final with signature lines for the teacher and the acta signers', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $curso = ActasFinales::curso();
    ActasFinales::preActa(ActasFinales::estudiante($curso['grupo'], 'Quispe'), [$curso['parcial']->id => '15.00', $curso['final']->id => '14.00', $curso['participacion']->id => '16.00']);
    app(CerrarActa::class)($curso['grupo']->id, $curso['docente']->id, $curso['usuario']);
    $actaId = (int) Acta::query()->where('grupo_id', $curso['grupo']->id)->value('id');

    expect(htmlDelPdf(app(GenerarActaFinalPdf::class)($actaId)))->toContain('Vega Ríos, Carlos', 'Docente del curso', 'Mg. Luis Paredes Vega', 'Coordinador Académico')
        ->not->toContain('Dr. Mario Huerta Ríos');
});

it('keeps the signers a document was issued with, even if a signer changes later', function (): void {
    $constancia = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);
    Firmante::query()->where('nombre', 'Dr. Mario Huerta Ríos')->update(['nombre' => 'Dr. Otro Nombre']);

    expect(htmlDelPdf(app(GenerarDocumentoPdf::class)($constancia->id)))->toContain('Dr. Mario Huerta Ríos')->not->toContain('Dr. Otro Nombre');
});
