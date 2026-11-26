<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\CreatePago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->cajero = User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Registrar->value,
    ]);
    $this->persona = Persona::factory()->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral]);
    $this->concepto = Concepto::factory()->create(['nombre' => 'Derecho de matrícula']);
    $this->tarifa = Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'vigente_desde' => '2020-01-01']);
});

function datosDelRegistro(array $cambios = []): array
{
    return [
        'persona_id' => test()->persona->id,
        'concepto_id' => test()->concepto->id,
        'medio_pago_id' => (string) MedioDePago::idDe('caja_presencial'),
        'numero_operacion' => 'rc-000123',
        'fecha_pago' => now('America/Lima')->toDateString(),
        'monto' => '150.00',
        'recibo' => UploadedFile::fake()->image('recibo.jpg'),
        ...$cambios,
    ];
}

it('registers a walk-in payment with its receipt as pending, recording the cashier', function (): void {
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro())
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Pago registrado');

    $pago = Pago::query()->sole();
    expect($pago)
        ->estado->toBe(EstadoPago::Pendiente)
        ->persona_id->toBe($this->persona->id)
        ->tarifa_id->toBe($this->tarifa->id)
        ->numero_operacion->toBe('RC-000123')
        ->registrado_por->toBe($this->cajero->id);
    Storage::disk('local')->assertExists($pago->recibo_path);
});

it('shows a broken business rule and registers nothing', function (): void {
    $this->tarifa->update(['tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro())
        ->call('create')
        ->assertNotified('No hay una tarifa vigente para este concepto en la fecha de pago.');

    expect(Pago::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles('recibos'))->toBe([]);
});

it('validates the registration form', function (array $cambios, array $errores): void {
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro($cambios))
        ->call('create')
        ->assertHasFormErrors($errores);

    expect(Pago::query()->count())->toBe(0);
})->with([
    'missing data' => [['persona_id' => null, 'numero_operacion' => '', 'monto' => null], ['persona_id' => 'required', 'numero_operacion' => 'required', 'monto' => 'required']],
    'receipt that is not an image or PDF' => [['recibo' => UploadedFile::fake()->create('recibo.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], ['recibo']],
    'receipt too large' => [['recibo' => UploadedFile::fake()->create('recibo.pdf', 6000, 'application/pdf')], ['recibo']],
    'future payment date' => [['fecha_pago' => now()->addDay()->toDateString()], ['fecha_pago']],
    'zero amount' => [['monto' => '0'], ['monto']],
    'operation number too long' => [['numero_operacion' => str_repeat('9', 41)], ['numero_operacion']],
]);

it('offers registration only to users with the register permission', function (): void {
    $soloVer = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value]);

    $this->actingAs($soloVer)->get(PagoResource::getUrl('create'))->assertForbidden();
    Livewire::test(ListPagos::class)->assertActionHidden('create');

    $this->actingAs($this->cajero);
    Livewire::test(ListPagos::class)->assertActionVisible('create');
});

it('defaults the payment date to today in Lima, also late in the evening', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 22:30', 'America/Lima'));
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)->assertSchemaStateSet(['fecha_pago' => '2026-10-24']);
});

it('refuses an amount with more than two decimals and accepts one with two', function (): void {
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro(['monto' => '150.555']))
        ->call('create')
        ->assertHasFormErrors(['monto' => 'El monto admite como máximo dos decimales.']);
    expect(Pago::query()->count())->toBe(0);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro(['monto' => '150.55']))
        ->call('create')
        ->assertHasNoFormErrors();
    expect(Pago::query()->sole()->monto)->toBe('150.55');
});

it('asks the cashier for the level when the concept has level fees and stores that fee', function (): void {
    $this->tarifa->update(['vigente_hasta' => '2020-01-01']);
    $intermedio = Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'nivel' => 'intermedio', 'monto' => '60.00', 'vigente_desde' => '2020-01-02']);
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'nivel' => 'basico', 'monto' => '50.00', 'vigente_desde' => '2020-01-02']);
    $this->actingAs($this->cajero);

    Livewire::test(CreatePago::class)
        ->fillForm(datosDelRegistro(['monto' => '60.00']))
        ->call('create')
        ->assertHasFormErrors(['nivel' => 'required'])
        ->fillForm(['nivel' => 'intermedio'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Pago::query()->sole()->tarifa_id)->toBe($intermedio->id);
});
