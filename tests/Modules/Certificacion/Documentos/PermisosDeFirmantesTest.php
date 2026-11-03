<?php

declare(strict_types=1);

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\DenominacionDelAnioResource;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages\CreateDenominacionDelAnio;
use App\Modules\Certificacion\Filament\Resources\Firmantes\FirmanteResource;
use App\Modules\Certificacion\Filament\Resources\Firmantes\Pages\CreateFirmante;
use App\Modules\Certificacion\Models\DenominacionDelAnio;
use App\Modules\Certificacion\Models\Firmante;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;

function personalConPermisos(PermisoCertificacion ...$permisos): User
{
    return User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...array_map(fn (PermisoCertificacion $permiso): string => $permiso->value, $permisos)]);
}

function firmante(bool $firmaActas): Firmante
{
    return Firmante::query()->create(['cargo' => 'Director', 'nombre' => 'Dr. Prueba Sintética', 'firma_certificados' => ! $firmaActas, 'firma_constancias' => false, 'firma_actas' => $firmaActas, 'orden' => 1, 'vigente_desde' => '2027-01-01']);
}

it('lets the documents staff without the actas permission keep only the signers that do not sign actas', function (): void {
    $documentos = personalConPermisos(PermisoCertificacion::Documentos);

    Livewire::actingAs($documentos)->test(CreateFirmante::class)
        ->assertFormFieldIsDisabled('firma_actas')
        ->fillForm(['cargo' => 'Directora', 'nombre' => 'Dra. Sintética', 'firma_certificados' => true, 'firma_actas' => true, 'orden' => 1, 'vigente_desde' => '2027-01-01'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Firmante::query()->sole()->firma_actas)->toBeFalse();

    $this->actingAs($documentos)->get(FirmanteResource::getUrl('edit', ['record' => firmante(firmaActas: false)]))->assertOk();
    $this->actingAs($documentos)->get(FirmanteResource::getUrl('edit', ['record' => firmante(firmaActas: true)]))->assertForbidden();
});

it('lets the staff with the documents and actas permissions keep the signers of actas', function (): void {
    $ambos = personalConPermisos(PermisoCertificacion::Documentos, PermisoCertificacion::Actas);

    Livewire::actingAs($ambos)->test(CreateFirmante::class)
        ->fillForm(['cargo' => 'Director', 'nombre' => 'Dr. Sintético', 'firma_actas' => true, 'orden' => 1, 'vigente_desde' => '2027-01-01'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Firmante::query()->sole()->firma_actas)->toBeTrue();
    $this->actingAs($ambos)->get(FirmanteResource::getUrl('edit', ['record' => firmante(firmaActas: true)]))->assertOk();
});

it('keeps the signers away from the staff with only the actas permission', function (): void {
    $actas = personalConPermisos(PermisoCertificacion::Actas);

    $this->actingAs($actas)->get(FirmanteResource::getUrl('index'))->assertForbidden();
    $this->actingAs($actas)->get(FirmanteResource::getUrl('edit', ['record' => firmante(firmaActas: true)]))->assertForbidden();
});

it('needs the documents and actas permissions to register or edit the denomination of the year', function (array $permisos, int $listar, int $cambiar): void {
    $usuario = personalConPermisos(...$permisos);
    $denominacion = DenominacionDelAnio::query()->create(['anio' => 2027, 'texto' => 'Año de la Prueba Sintética']);

    $this->actingAs($usuario)->get(DenominacionDelAnioResource::getUrl('index'))->assertStatus($listar);
    $this->actingAs($usuario)->get(DenominacionDelAnioResource::getUrl('create'))->assertStatus($cambiar);
    $this->actingAs($usuario)->get(DenominacionDelAnioResource::getUrl('edit', ['record' => $denominacion]))->assertStatus($cambiar);
})->with([
    'documents only' => [[PermisoCertificacion::Documentos], 200, 403],
    'actas only' => [[PermisoCertificacion::Actas], 403, 403],
    'documents and actas' => [[PermisoCertificacion::Documentos, PermisoCertificacion::Actas], 200, 200],
]);

it('registers the denomination of the year with the documents and actas permissions', function (): void {
    Livewire::actingAs(personalConPermisos(PermisoCertificacion::Documentos, PermisoCertificacion::Actas))->test(CreateDenominacionDelAnio::class)
        ->fillForm(['anio' => 2028, 'texto' => 'Año de Otra Prueba'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DenominacionDelAnio::query()->sole()->texto)->toBe('Año de Otra Prueba');
});
