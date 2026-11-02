<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\DenominacionDelAnio;
use App\Modules\Certificacion\Models\Firmante;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->firmante = Firmante::query()->create(['cargo' => 'Coordinador académico', 'nombre' => 'Mg. Luis Paredes Vega', 'firma_certificados' => false, 'firma_constancias' => false, 'firma_actas' => true, 'orden' => 1, 'vigente_desde' => '2027-01-01']);
    DenominacionDelAnio::query()->create(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética']);
    $this->curso = ActasFinales::curso();
    ActasFinales::preActa(ActasFinales::estudiante($this->curso['grupo'], 'Quispe', '70000001'), [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '14.00']);
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);
    $this->acta = Acta::query()->sole();
});

function htmlDelActa(): string
{
    $pdf = app(GenerarActaFinalPdf::class)(test()->acta->id);

    return view($pdf->viewName, $pdf->viewData)->render();
}

function cambiarFirmanteYDenominacion(): void
{
    test()->firmante->update(['cargo' => 'Coordinadora general', 'nombre' => 'Dra. Rosa Nueva Firmante']);
    Firmante::query()->create(['cargo' => 'Director del CILN', 'nombre' => 'Dr. Agregado Después', 'firma_certificados' => false, 'firma_constancias' => false, 'firma_actas' => true, 'orden' => 2, 'vigente_desde' => '2027-01-01']);
    DenominacionDelAnio::query()->where('anio', 2027)->update(['texto' => 'Texto cambiado después']);
}

it('keeps on a closed acta the signers and the denomination in force when it was closed', function (): void {
    cambiarFirmanteYDenominacion();

    $pdf = htmlDelActa();

    expect($this->acta->refresh())
        ->firmas->toEqual([['nombre' => 'Mg. Luis Paredes Vega', 'cargo' => 'Coordinador académico']])
        ->denominacion->toBe('Año de la Prueba Sintética')
        ->and(collect(['Mg. Luis Paredes Vega', 'Coordinador académico', '«Año de la Prueba Sintética»', 'Docente del curso'])->every(fn (string $texto): bool => str_contains($pdf, $texto)))->toBeTrue()
        ->and(collect(['Dra. Rosa Nueva Firmante', 'Coordinadora general', 'Dr. Agregado Después', 'Texto cambiado después'])->contains(fn (string $texto): bool => str_contains($pdf, $texto)))->toBeFalse();
});

it('freezes again the signers and the denomination in force when a rectified acta is closed again', function (): void {
    cambiarFirmanteYDenominacion();
    app(AutorizarRectificacion::class)($this->acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);

    $pdf = htmlDelActa();

    expect($this->acta->refresh())
        ->firmas->toEqual([['nombre' => 'Dra. Rosa Nueva Firmante', 'cargo' => 'Coordinadora general'], ['nombre' => 'Dr. Agregado Después', 'cargo' => 'Director del CILN']])
        ->denominacion->toBe('Texto cambiado después')
        ->and(collect(['Dra. Rosa Nueva Firmante', 'Dr. Agregado Después', '«Texto cambiado después»'])->every(fn (string $texto): bool => str_contains($pdf, $texto)))->toBeTrue()
        ->and(collect(['Mg. Luis Paredes Vega', 'Año de la Prueba Sintética'])->contains(fn (string $texto): bool => str_contains($pdf, $texto)))->toBeFalse();
});

it('prints the signers and the denomination in force on its closing date for an acta closed before they were frozen', function (): void {
    DB::statement('ALTER TABLE cer_actas DISABLE TRIGGER cer_actas_bloqueo');
    DB::table('cer_actas')->where('id', $this->acta->id)->update(['firmas' => null, 'denominacion' => null]);
    DB::statement('ALTER TABLE cer_actas ENABLE TRIGGER cer_actas_bloqueo');
    cambiarFirmanteYDenominacion();

    $pdf = htmlDelActa();

    expect(collect(['Dra. Rosa Nueva Firmante', 'Dr. Agregado Después', '«Texto cambiado después»'])->every(fn (string $texto): bool => str_contains($pdf, $texto)))->toBeTrue();
});
