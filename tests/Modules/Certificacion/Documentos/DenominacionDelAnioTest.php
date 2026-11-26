<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Contracts\DenominacionDelAnio as DenominacionDelAnioContrato;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\DenominacionDelAnioResource;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages\CreateDenominacionDelAnio;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\DenominacionDelAnio;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-10 11:30', 'America/Lima'));
    $this->emisor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value]);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado']]);
});

function htmlDe(PdfBuilder $pdf): string
{
    return view($pdf->viewName, $pdf->viewData)->render();
}

it('lets the documents staff register one denomination per year and keeps others away', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);
    $this->actingAs($otro)->get(DenominacionDelAnioResource::getUrl('index'))->assertForbidden();
    $this->emisor->givePermissionTo(PermisoCertificacion::Actas->value);

    Livewire::actingAs($this->emisor)->test(CreateDenominacionDelAnio::class)
        ->fillForm(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética'])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::actingAs($this->emisor)->test(CreateDenominacionDelAnio::class)
        ->fillForm(['anio' => 2027, 'texto' => 'Otra para el mismo año'])
        ->call('create')
        ->assertHasFormErrors(['anio' => 'unique']);

    expect(app(DenominacionDelAnioContrato::class)(2027))->toBe('Año de la Prueba Sintética')
        ->and(app(DenominacionDelAnioContrato::class)(2026))->toBeNull()
        ->and(fn () => DB::table('cer_denominaciones')->insert(['anio' => 2028, 'texto' => '  ', 'created_at' => now(), 'updated_at' => now()]))->toThrow(QueryException::class);
});

it('prints the denomination of the issue year and keeps it after the text changes', function (): void {
    DenominacionDelAnio::query()->create(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética']);
    $constancia = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    DenominacionDelAnio::query()->where('anio', 2027)->update(['texto' => 'Texto cambiado después']);

    expect(htmlDe(app(GenerarDocumentoPdf::class)($constancia->id)))->toContain('«Año de la Prueba Sintética»')->not->toContain('Texto cambiado después');
});

it('leaves the line out when the year has no denomination', function (): void {
    $constancia = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    expect(htmlDe(app(GenerarDocumentoPdf::class)($constancia->id)))->not->toContain('«');
});

it('prints on the acta final the denomination of the year it was validated', function (): void {
    DenominacionDelAnio::query()->create(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética']);
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $curso = ActasFinales::curso();
    ActasFinales::preActa(ActasFinales::estudiante($curso['grupo'], 'Quispe'), [$curso['parcial']->id => '15.00', $curso['final']->id => '14.00']);
    app(CerrarActa::class)($curso['grupo']->id, $curso['docente']->id, $curso['usuario']);

    expect(htmlDe(app(GenerarActaFinalPdf::class)((int) Acta::query()->value('id'))))->toContain('«Año de la Prueba Sintética»');
});

it('gives a document issued before the denomination existed the one of its issue year', function (): void {
    DenominacionDelAnio::query()->create(['anio' => 2026, 'texto' => 'Año anterior de prueba']);
    $antiguo = Documento::query()->create([
        'codigo' => 'CILN-ANTIGUO001', 'tipo' => TipoDocumento::ConstanciaNotas, 'persona_id' => $this->ana->id, 'estudiante_nombre' => 'Alva Prueba, Ana',
        'estudiante_documento' => 'DNI 70000001', 'idioma' => 'Inglés', 'detalle' => ['notas' => []], 'emitido_por' => $this->emisor->id, 'emitido_en' => '2026-11-20 10:00:00-05',
    ]);

    expect(htmlDe(app(GenerarDocumentoPdf::class)($antiguo->id)))->toContain('«Año anterior de prueba»');
});
