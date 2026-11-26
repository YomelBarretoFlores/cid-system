<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Actions\GuardarDatosInstitucionales;
use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Pages\EditarDatosInstitucionales;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
});

/**
 * @return array<string, string>
 */
function datosEditados(array $cambios = []): array
{
    return [
        'universidad' => 'Universidad Sintética de Prueba', 'lema' => 'Un lema de prueba', 'centro' => 'Centro de Idiomas',
        'direccion' => 'Av. de Prueba 123 – Ciudad Prueba', 'telefono' => '000-000000', 'web' => 'prueba.test', 'facebook' => 'pruebaciln',
        'ciudad' => 'Ciudad Prueba', 'formato_numero' => 'N.º {numero:06}-{anio}', 'formato_numero_constancia_notas' => 'N° {anio} - {numero:05}',
        ...$cambios,
    ];
}

it('starts with the texts the documents print today and the real address of the center', function (): void {
    $datos = app(DatosInstitucionales::class)();

    expect($datos->universidad)->toBe('Universidad Nacional Santiago Antúnez de Mayolo')
        ->and($datos->lema)->toBe('Una Nueva Universidad para el Desarrollo')
        ->and($datos->centro)->toBe('Centro de Idiomas y Lenguas Nativas')
        ->and($datos->direccion)->toBe('Prolongación Simón Bolívar S/N – Huaraz')
        ->and($datos->telefono)->toBe('043-640020 / Anexo 3105')
        ->and($datos->web)->toBe('cid.unasam.edu.pe')
        ->and($datos->facebook)->toBe('cidunasam')
        ->and($datos->ciudad)->toBe('Huaraz')
        ->and($datos->formatoNumero)->toBe('N.º {numero:06}-{anio}')
        ->and($datos->formatoNumeroConstanciaNotas)->toBe('N° {anio} - {numero:05}');
});

it('saves the institutional data from the settings page and the documents print them', function (): void {
    $ana = Persona::factory()->create(['numero_documento' => '70000001']);
    ActasFinales::historial($ana, 'Italiano', [[1, '15.00', 'aprobado']]);

    Livewire::actingAs($this->encargado)->test(EditarDatosInstitucionales::class)
        ->assertSet('data.ciudad', 'Huaraz')
        ->fillForm(datosEditados(['formato_numero_constancia_notas' => 'CN-{anio}/{numero:03}']))
        ->call('guardar')
        ->assertHasNoFormErrors();

    $constancia = app(EmitirConstanciaDeNotas::class)($ana->id, 'Italiano', NivelCurricular::Basico, $this->encargado);
    $matricula = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaMatricula, $ana->id, ['idioma' => 'Italiano', 'detalle' => []], $this->encargado);
    $pdf = app(GenerarDocumentoPdf::class)($constancia->id);
    $html = view($pdf->viewName, [...$pdf->viewData, 'qr' => CodigoQr::comoDataUri('x')])->render();

    expect($html)->toContain('Ciudad Prueba, 16 de Noviembre de 2026')
        ->and($html)->toContain('UNIVERSIDAD SINTÉTICA DE PRUEBA')
        ->and($html)->toContain('Un lema de prueba')
        ->and($html)->toContain('EL DIRECTOR DEL CENTRO DE IDIOMAS DE LA UNIVERSIDAD SINTÉTICA DE PRUEBA')
        ->and($html)->toContain('Av. de Prueba 123 – Ciudad Prueba – Telf: 000-000000')
        ->and($html)->toContain('Web: prueba.test / Facebook: pruebaciln')
        ->and($html)->toContain('CONSTANCIA DE NOTAS CN-2026/001')
        ->and(app(GenerarDocumentoPdf::class)($matricula->id)->viewData['lugarYFecha'])->toBe('Ciudad Prueba, 16 de noviembre de 2026')
        ->and(DB::table('aud_registros')->where(['tabla' => 'cer_datos_institucionales', 'operacion' => 'UPDATE'])->count())->toBe(1);
});

it('keeps the number an issued document printed when the pattern changes later', function (): void {
    $ana = Persona::factory()->create();
    ActasFinales::historial($ana, 'Italiano', [[1, '15.00', 'aprobado']]);
    $constancia = app(EmitirConstanciaDeNotas::class)($ana->id, 'Italiano', NivelCurricular::Basico, $this->encargado);
    $matricula = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaMatricula, $ana->id, ['idioma' => 'Italiano', 'detalle' => []], $this->encargado);

    app(GuardarDatosInstitucionales::class)(datosEditados(['formato_numero' => 'X-{numero}', 'formato_numero_constancia_notas' => 'Y-{numero}']), $this->encargado);
    $nueva = app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaMatricula, $ana->id, ['idioma' => 'Italiano', 'detalle' => []], $this->encargado);

    expect($constancia->fresh()->numeroOficial())->toBe('N° 2026 - 00001')
        ->and($matricula->fresh()->numeroOficial())->toBe('N.º 000001-2026')
        ->and($nueva->numeroOficial())->toBe('X-2');
});

it('prints the new footer and city on the other documents too', function (): void {
    app(GuardarDatosInstitucionales::class)(datosEditados(), $this->encargado);

    $html = view('components.pdf.documento', ['titulo' => 'PRUEBA', 'slot' => new HtmlString('')])->render();

    expect($html)->toContain('Centro de Idiomas')
        ->toContain('Av. de Prueba 123 – Ciudad Prueba – Telf: 000-000000')
        ->toContain('Web: prueba.test / Facebook: pruebaciln');
});

it('refuses blank texts and number patterns without the number', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarDatosInstitucionales::class)(datosEditados($cambios), $this->encargado))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(app(DatosInstitucionales::class)()->ciudad)->toBe('Huaraz');
})->with([
    'blank city' => [['ciudad' => '  '], 'Completa la ciudad, hasta 60 caracteres.'],
    'pattern without number' => [['formato_numero' => 'N.º {anio}'], 'El formato del número debe incluir {numero} o {numero:N}.'],
    'grade record pattern without number' => [['formato_numero_constancia_notas' => 'CN'], 'El formato del número debe incluir {numero} o {numero:N}.'],
]);

it('keeps a single row of institutional data in the database', function (): void {
    expect(fn () => DB::table('cer_datos_institucionales')->insert(['id' => 2, ...datosEditados(), 'created_at' => now(), 'updated_at' => now()]))
        ->toThrow(QueryException::class);
});

it('lets only staff with the documents and actas permissions see and save the institutional data', function (array $permisos): void {
    $ajeno = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...$permisos]);

    $this->actingAs($ajeno)->get(EditarDatosInstitucionales::getUrl())->assertForbidden();
    expect(fn () => app(GuardarDatosInstitucionales::class)(datosEditados(), $ajeno))->toThrow(AuthorizationException::class);
    $this->actingAs($this->encargado)->get(EditarDatosInstitucionales::getUrl())->assertOk()->assertSee('Datos institucionales');
})->with([
    'documents only' => [[PermisoCertificacion::Documentos->value]],
    'actas only' => [[PermisoCertificacion::Actas->value]],
]);
