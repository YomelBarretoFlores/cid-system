<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Data\VerificacionDeDocumento;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 23:30', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.50', 'aprobado'], [3, '16.25', 'aprobado'], [4, '13.00', 'aprobado'], [5, null, 'nsp']]);
    $this->certificado = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
});

it('builds the PDF of a document with its QR pointing to the public verification page', function (): void {
    $pdf = app(GenerarDocumentoPdf::class)($this->certificado->id);

    expect($pdf->viewName)->toBe('certificacion::pdf.certificado-de-nivel')
        ->and($pdf->downloadName)->toBe("certificado-de-nivel-{$this->certificado->codigo}.pdf")
        ->and($pdf->viewData['verificacion'])->toBe(route('verificacion.documento', $this->certificado->codigo))
        ->and($pdf->viewData['qr'])->toStartWith('data:image/png;base64,')
        ->and($pdf->viewData['emitidoEl'])->toBe('10 abr 2027');
});

it('renders the certificate and the grade record with the accreditation data, the code, the issue date and the QR', function (): void {
    $notas = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);
    $datos = fn (Documento $documento): array => ['documento' => $documento, 'verificacion' => 'https://ciln.test/verificar/'.$documento->codigo, 'qr' => CodigoQr::comoDataUri('x'), 'emitidoEl' => '10 abr 2027'];

    expect(view('certificacion::pdf.certificado-de-nivel', $datos($this->certificado))->render())
        ->toContain('CERTIFICADO', 'de culminación de nivel', 'Alva Prueba, Ana', 'DNI 70000001', 'nivel <strong>Básico</strong> de <strong>Inglés</strong>', '240 horas lectivas', '14.69', "Código único: <strong style=\"color: #1d1b16;\">{$this->certificado->codigo}</strong>", 'Fecha de emisión: 10 abr 2027', 'data:image/png;base64,')
        ->and(view('certificacion::pdf.constancia-de-notas', $datos($notas))->render())
        ->toContain('CONSTANCIA DE NOTAS', 'Básico 1 (1)', '14.00', 'Aprobado', 'NSP', "Código único: {$notas->codigo}");
});

it('refuses the PDF of a voided document', function (): void {
    app(AnularDocumento::class)($this->certificado->id, 'Se emitió con un nombre mal escrito.', $this->emisor);

    expect(fn () => app(GenerarDocumentoPdf::class)($this->certificado->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, "El documento {$this->certificado->codigo} está anulado; ya no se emite su PDF.");
});

it('confirms a valid document on the public page with the student, level, hours and grade, out of search engines', function (): void {
    $this->get(route('verificacion.documento', $this->certificado->codigo))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSeeInOrder(['Documento auténtico y vigente', 'Certificado de culminación de nivel', 'Alva Prueba, Ana', 'DNI ••••0001', 'Inglés', 'Básico', '240', '14.69', '4 ciclos aprobados del nivel', $this->certificado->codigo, '10 abr 2027'])
        ->assertDontSee('70000001');
});

it('shows a voided document as void without its reason', function (): void {
    app(AnularDocumento::class)($this->certificado->id, 'Se emitió con un nombre mal escrito.', $this->emisor);

    $this->get(route('verificacion.documento', $this->certificado->codigo))
        ->assertOk()
        ->assertSee('Documento anulado el 10 abr 2027')
        ->assertDontSee('Documento auténtico y vigente')
        ->assertDontSee('nombre mal escrito');
});

it('finds a code typed in lowercase, without the dash or from the form, and answers 404 for an unknown one', function (): void {
    $sinGuion = strtolower(str_replace('-', '', $this->certificado->codigo));

    $this->get(route('verificacion.documento', $sinGuion))->assertOk()->assertSee('Documento auténtico y vigente');
    $this->get(route('verificacion.documento').'?codigo='.urlencode(" {$sinGuion} "))->assertRedirect(route('verificacion.documento', $this->certificado->codigo));
    $this->get(route('verificacion.documento'))->assertOk()->assertSee('Escribe el código único del documento');
    $this->get(route('verificacion.documento', 'CILN-0000000000'))
        ->assertNotFound()
        ->assertSee('No encontramos un documento con el código CILN-0000000000.');
});

it('limits how often the public page can be asked', function (): void {
    foreach (range(1, 30) as $intento) {
        $this->get(route('verificacion.documento', 'CILN-0000000000'))->assertNotFound();
    }

    $this->get(route('verificacion.documento', $this->certificado->codigo))->assertTooManyRequests();
});

it('hides all but the last four characters of the document number', function (string $documento, string $oculto): void {
    expect(VerificacionDeDocumento::ocultar($documento))->toBe($oculto);
})->with([
    ['DNI 70000001', 'DNI ••••0001'],
    ['Carné de Extranjería 786994660', 'Carné de Extranjería •••••4660'],
    ['Pasaporte AB12', 'Pasaporte AB12'],
]);

it('renders the enrollment record with the group, cycle, period, schedule and enrollment date', function (): void {
    $documento = new Documento([
        'codigo' => 'CILN-ABCDEFGHJK', 'tipo' => 'constancia_matricula', 'estudiante_nombre' => 'Alva Prueba, Ana', 'estudiante_documento' => 'DNI 70000001',
        'idioma' => 'Portugués', 'nivel' => 'basico',
        'detalle' => ['grupo' => 'POR-2-T', 'ciclo_numero' => 2, 'periodo' => 'Marzo 2027', 'modalidad' => 'Presencial', 'horario' => ['Martes 16:00–18:00', 'Jueves 16:00–18:00'], 'matriculado_el' => '2027-02-20', 'codigo_matricula' => '01J'],
    ]);

    expect(view('certificacion::pdf.constancia-de-matricula', ['documento' => $documento, 'verificacion' => 'https://ciln.test/verificar/CILN-ABCDEFGHJK', 'qr' => CodigoQr::comoDataUri('x'), 'emitidoEl' => '10 abr 2027'])->render())
        ->toContain('CONSTANCIA DE MATRÍCULA', 'Alva Prueba, Ana', 'POR-2-T', 'Ciclo 2 · Básico', 'Marzo 2027', 'Martes 16:00–18:00 · Jueves 16:00–18:00', '20/02/2027', 'Código único: CILN-ABCDEFGHJK');
});

it('sends a typed code that cannot be a document code back to the empty form', function (): void {
    $this->get(route('verificacion.documento').'?codigo='.urlencode(str_repeat('A', 40)))->assertRedirect(route('verificacion.documento'));
    $this->get(route('verificacion.documento').'?codigo='.urlencode('***'))->assertRedirect(route('verificacion.documento'));
});

it('labels the level of an enrollment record as the level of the course, not a level reached', function (): void {
    $documento = Documento::query()->create([
        'codigo' => 'CILN-MATRICULA01', 'tipo' => 'constancia_matricula', 'persona_id' => $this->ana->id, 'estudiante_nombre' => 'Alva Prueba, Ana',
        'estudiante_documento' => 'DNI 70000001', 'idioma' => 'Portugués', 'nivel' => 'basico', 'emitido_por' => $this->emisor->id, 'emitido_en' => now(),
        'detalle' => ['grupo' => 'POR-2-T', 'ciclo_numero' => 2, 'periodo' => 'Marzo 2027', 'modalidad' => 'Presencial', 'horario' => [], 'matriculado_el' => '2027-02-20', 'codigo_matricula' => '01J'],
    ]);

    $this->get(route('verificacion.documento', $documento->codigo))
        ->assertOk()
        ->assertSeeInOrder(['Nivel', 'Básico'])
        ->assertDontSee('Nivel alcanzado');
    $this->get(route('verificacion.documento', $this->certificado->codigo))->assertSee('Nivel alcanzado');
});
