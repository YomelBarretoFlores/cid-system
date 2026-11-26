<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeAcreditacion;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->luis = Persona::factory()->create(['apellido_paterno' => 'Paz', 'apellido_materno' => 'Prueba', 'nombres' => 'Luis', 'numero_documento' => '70000002']);
    ActasFinales::historial($this->luis, 'Inglés', [[1, '15.00', 'aprobado'], [2, '17.00', 'aprobado'], [5, '14.00', 'aprobado']]);
});

function acreditacion(string $mencion = 'Maestría en Educación Superior'): Documento
{
    return app(EmitirConstanciaDeAcreditacion::class)(test()->luis->id, 'Inglés', NivelCurricular::Basico, $mencion, test()->emisor);
}

it('issues the accreditation record of one level with its passed cycles and the degree mention', function (): void {
    $documento = acreditacion();

    expect($documento)->tipo->toBe(TipoDocumento::ConstanciaAcreditacion)->nivel->toBe(NivelCurricular::Basico)->idioma->toBe('Inglés')
        ->and($documento->detalle['mencion'])->toBe('Maestría en Educación Superior')
        ->and(array_column($documento->detalle['filas'], 'ciclo'))->toBe(['I', 'II'])
        ->and($documento->detalle['dni'])->toBe('70000002')
        ->and($documento->detalle['firmas'])->toBeArray();
});

it('numbers accreditation records on their own each year with the frozen grade record pattern', function (): void {
    app(EmitirConstanciaDeNotas::class)($this->luis->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    $primera = acreditacion();
    DB::table('cer_datos_institucionales')->update(['formato_numero_constancia_notas' => 'N° {numero:03}/{anio}']);
    $segunda = acreditacion('Doctorado en Educación');

    expect($primera)->numero->toBe(1)->anio_numero->toBe(2026)
        ->and($primera->numeroOficial())->toBe('N° 2026 - 00001')
        ->and($segunda->numero)->toBe(2)
        ->and($segunda->numeroOficial())->toBe('N° 002/2026')
        ->and($primera->refresh()->numeroOficial())->toBe('N° 2026 - 00001');
});

it('refuses a level without a passed cycle or a record without the mention', function (): void {
    expect(fn () => app(EmitirConstanciaDeAcreditacion::class)($this->luis->id, 'Inglés', NivelCurricular::Avanzado, 'Maestría', $this->emisor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La persona no tiene ciclos aprobados de Inglés en el nivel Avanzado.')
        ->and(fn (): Documento => acreditacion('  '))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la mención del grado de maestría o doctorado.')
        ->and(Documento::query()->count())->toBe(0);
});

it('prints the accreditation title, the degree, the number and the QR, and verifies publicly with its type', function (): void {
    $documento = acreditacion();
    $pdf = app(GenerarDocumentoPdf::class)($documento->id);
    $html = view($pdf->viewName, [...$pdf->viewData, 'qr' => CodigoQr::comoDataUri('x')])->render();

    expect($pdf->viewName)->toBe('certificacion::pdf.constancia-de-acreditacion')
        ->and($html)->toContain('ACREDITACIÓN DE CONOCIMIENTO DE IDIOMA EXTRANJERO', 'N° Registro: <strong>2026 - 00001</strong>', 'Válido para obtener el Grado de Maestro en Educación Superior.', $documento->codigo);

    $this->get(route('verificacion.documento', $documento->codigo))
        ->assertOk()
        ->assertSeeText('Constancia de acreditación')
        ->assertSeeText('Documento auténtico y vigente')
        ->assertSeeText('2 ciclos aprobados del nivel · Mención: Maestría en Educación Superior');
});

it('issues it for a trámite through the contract, with the mention the trámite sends', function (): void {
    $emitido = app(DocumentoParaTramite::class)(TipoDocumento::ConstanciaAcreditacion, $this->luis->id, ['idioma' => 'Inglés', 'nivel' => 'basico', 'mencion_completa' => 'Doctorado en Educación'], $this->emisor);

    expect(Documento::query()->findOrFail($emitido->id))->tipo->toBe(TipoDocumento::ConstanciaAcreditacion)->detalle->toHaveKey('mencion', 'Doctorado en Educación');
});
