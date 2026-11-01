<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Resources\MediosDePago\MedioDePagoResource;
use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\CreateMedioDePago;
use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\EditMedioDePago;
use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\ListMediosDePago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Configurar->value]);
});

function migracionDeMediosDePago(): Migration
{
    return require base_path('app/Modules/Pagos/Database/Migrations/2026_11_01_100200_turn_payment_channels_into_a_catalog.php');
}

it('offers the payment channels of the CILN in order with the labels they had before, Tesorería UNASAM included', function (): void {
    expect(MedioDePago::query()->activos()->pluck('nombre', 'codigo')->all())->toBe([
        'caja_presencial' => 'Caja presencial',
        'tesoreria_unasam' => 'Tesorería UNASAM',
        'pagalo_pe' => 'Págalo.pe',
        'banco_nacion' => 'Banco de la Nación',
        'transferencia' => 'Transferencia bancaria',
        'otro' => 'Otro',
    ]);
});

it('keeps the payment channel screens away from users without the configuration permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);

    $this->actingAs($cajero)->get(MedioDePagoResource::getUrl('index'))->assertForbidden();
});

it('creates a payment channel with a code taken from its name, and renames or deactivates it later', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateMedioDePago::class)
        ->fillForm(['nombre' => 'Agente BCP', 'orden' => 7])
        ->call('create')
        ->assertHasNoFormErrors();

    $medio = MedioDePago::query()->where('codigo', 'agente_bcp')->sole();
    expect([$medio->nombre, $medio->orden, $medio->activo])->toBe(['Agente BCP', 7, true]);

    Livewire::test(EditMedioDePago::class, ['record' => $medio->getRouteKey()])
        ->fillForm(['nombre' => 'Agente BCP Huaraz', 'activo' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($medio->refresh())->nombre->toBe('Agente BCP Huaraz')->codigo->toBe('agente_bcp')->activo->toBeFalse();
});

it('refuses a second channel with the same name', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateMedioDePago::class)
        ->fillForm(['nombre' => 'Págalo.pe', 'orden' => 9])
        ->call('create')
        ->assertHasFormErrors(['nombre']);
});

it('lists the channels with no way to delete them', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(ListMediosDePago::class)
        ->assertCanSeeTableRecords(MedioDePago::query()->get())
        ->assertTableActionDoesNotExist('delete');
});

it('makes the database refuse deleting a channel used by a payment', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => DB::table('pag_medios_pago')->where('id', $pago->medio_pago_id)->delete())->toThrow(QueryException::class);
});

it('stops offering an inactive channel and refuses it for a new payment', function (): void {
    Storage::fake('local');
    MedioDePago::query()->where('codigo', 'transferencia')->update(['activo' => false]);
    $persona = Persona::factory()->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral]);
    $concepto = Concepto::factory()->create();
    Tarifa::factory()->for($concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '150.00', 'vigente_desde' => '2020-01-01']);
    $transferencia = MedioDePago::query()->where('codigo', 'transferencia')->value('id');

    expect(MedioDePago::query()->activos()->pluck('codigo')->all())->not->toContain('transferencia')
        ->and(fn () => app(RegistrarPago::class)(
            new RegistroDePago($persona->id, $concepto->id, $transferencia, 'OP-1', CarbonImmutable::parse('2027-03-01'), '150.00'),
            UploadedFile::fake()->createWithContent('r.pdf', "%PDF-1.4\n%%EOF\n"),
        ))->toThrow(ReglaDeNegocioIncumplida::class, 'El medio de pago no está disponible.')
        ->and(Pago::query()->count())->toBe(0);
});

it('keeps the channel of the payments stored before the catalog existed', function (): void {
    $pago = Pago::factory()->validado()->create(['medio_pago_id' => MedioDePago::query()->where('codigo', 'pagalo_pe')->value('id')]);
    migracionDeMediosDePago()->down();

    expect(DB::table('pag_pagos')->where('id', $pago->id)->value('medio_pago'))->toBe('pagalo_pe');

    migracionDeMediosDePago()->up();

    expect($pago->refresh()->medioDePago->codigo)->toBe('pagalo_pe')
        ->and($pago->estado->value)->toBe('validado');
});

it('keeps the operation number unique per channel among payments not rejected', function (): void {
    $pago = Pago::factory()->create(['numero_operacion' => 'OP-77']);

    expect(fn () => DB::transaction(fn () => Pago::factory()->create(['numero_operacion' => 'OP-77', 'medio_pago_id' => $pago->medio_pago_id])))->toThrow(QueryException::class, 'pag_pagos_operacion_unique');

    $otro = Pago::factory()->create(['numero_operacion' => 'OP-77', 'medio_pago_id' => MedioDePago::query()->where('codigo', 'tesoreria_unasam')->value('id')]);
    expect($otro->exists)->toBeTrue();
});
