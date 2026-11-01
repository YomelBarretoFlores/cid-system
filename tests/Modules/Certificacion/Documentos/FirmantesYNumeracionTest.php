<?php

declare(strict_types=1);

use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\Firmantes\FirmanteResource;
use App\Modules\Certificacion\Filament\Resources\Firmantes\Pages\CreateFirmante;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Models\Firmante;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->emisor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value]);
    $this->persona = Persona::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00', 'America/Lima'));
});

function emitirDocumento(TipoDocumento $tipo): Documento
{
    return app(EmisionDeDocumento::class)->emitir($tipo, test()->persona->id, ['idioma' => 'Inglés', 'detalle' => []], test()->emisor);
}

it('numbers each new document per type and year, from 1', function (): void {
    $primera = emitirDocumento(TipoDocumento::ConstanciaMatricula);
    $segunda = emitirDocumento(TipoDocumento::ConstanciaMatricula);
    $deNotas = emitirDocumento(TipoDocumento::ConstanciaNotas);
    $this->travelTo(CarbonImmutable::parse('2028-01-02 09:00', 'America/Lima'));
    $delAnioSiguiente = emitirDocumento(TipoDocumento::ConstanciaMatricula);

    expect(array_map(fn (Documento $documento): ?string => $documento->refresh()->numeroOficial(), [$primera, $segunda, $deNotas, $delAnioSiguiente]))
        ->toBe(['N.º 000001-2027', 'N.º 000002-2027', 'N.º 000001-2027', 'N.º 000001-2028']);
});

it('leaves the documents issued before numbering without a number', function (): void {
    $anterior = emitirDocumento(TipoDocumento::ConstanciaMatricula);
    DB::statement('ALTER TABLE cer_documentos DISABLE TRIGGER cer_documentos_bloqueo');
    DB::table('cer_documentos')->where('id', $anterior->id)->update(['numero' => null, 'anio_numero' => null]);
    DB::statement('ALTER TABLE cer_documentos ENABLE TRIGGER cer_documentos_bloqueo');

    expect($anterior->refresh()->numeroOficial())->toBeNull();
});

it('makes the database refuse a repeated number of one type and year', function (): void {
    $documento = emitirDocumento(TipoDocumento::ConstanciaMatricula);
    $copia = collect($documento->getAttributes())->except(['id', 'codigo'])->merge(['codigo' => 'CILN-0000000000', 'detalle' => '{}'])->all();

    expect(fn () => DB::transaction(fn (): bool => DB::table('cer_documentos')->insert($copia)))->toThrow(QueryException::class, 'cer_documentos_numero_unique');
});

it('prints the signers in force on the issue date for each kind of document, in their order', function (): void {
    Firmante::query()->create(['cargo' => 'Coordinador académico', 'nombre' => 'Mg. Luis Paredes Vega', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => true, 'orden' => 2, 'vigente_desde' => '2027-01-01']);
    Firmante::query()->create(['cargo' => 'Director del CILN', 'nombre' => 'Dr. Mario Huerta Ríos', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => false, 'orden' => 1, 'vigente_desde' => '2027-01-01']);
    Firmante::query()->create(['cargo' => 'Director del CILN', 'nombre' => 'Dr. Anterior', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => false, 'orden' => 1, 'vigente_desde' => '2026-01-01', 'vigente_hasta' => '2026-12-31']);

    $nombres = fn (GrupoDeFirma $grupo, string $fecha): array => Firmante::query()->firmanEn($grupo, CarbonImmutable::parse($fecha))->pluck('nombre')->all();

    expect($nombres(GrupoDeFirma::Certificados, '2027-03-10'))->toBe(['Dr. Mario Huerta Ríos', 'Mg. Luis Paredes Vega'])
        ->and($nombres(GrupoDeFirma::Actas, '2027-03-10'))->toBe(['Mg. Luis Paredes Vega'])
        ->and($nombres(GrupoDeFirma::Constancias, '2026-06-01'))->toBe(['Dr. Anterior']);
});

it('lets the documents staff register signers and keeps others away', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);
    $this->actingAs($otro)->get(FirmanteResource::getUrl('index'))->assertForbidden();

    Livewire::actingAs($this->emisor)->test(CreateFirmante::class)
        ->fillForm(['cargo' => 'Directora del CILN', 'nombre' => 'Dra. Ana Torres Lazo', 'firma_certificados' => true, 'firma_constancias' => true, 'firma_actas' => false, 'orden' => 1, 'vigente_desde' => '2027-01-01'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Firmante::query()->where('nombre', 'Dra. Ana Torres Lazo')->sole()->firma_certificados)->toBeTrue();

    Livewire::actingAs($this->emisor)->test(CreateFirmante::class)
        ->fillForm(['cargo' => 'Secretaria', 'nombre' => 'Sin documentos', 'firma_certificados' => false, 'firma_constancias' => false, 'firma_actas' => false, 'orden' => 3, 'vigente_desde' => '2027-01-01'])
        ->call('create')
        ->assertHasFormErrors(['firma_certificados']);
});
