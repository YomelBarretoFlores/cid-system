<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ViewPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    Event::fake([PagoValidado::class]);
    $this->cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);
    $this->travelTo(now()->startOfSecond());
});

function migracionDeBoletaOriginal(): Migration
{
    return require base_path('app/Modules/Pagos/Database/Migrations/2026_11_01_100000_require_the_original_voucher_to_validate_a_payment.php');
}

function pagoConTarifa(string $monto, string $tarifa): Pago
{
    $tarifa = Tarifa::factory()->create(['monto' => $tarifa]);

    return Pago::factory()->create(['concepto_id' => $tarifa->concepto_id, 'tarifa_id' => $tarifa->id, 'monto' => $monto]);
}

it('validates a payment once the cashier confirms holding the original voucher, recording who and when', function (): void {
    $pago = Pago::factory()->create();

    app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true);

    expect($pago->refresh())
        ->estado->toBe(EstadoPago::Validado)
        ->boleta_original_verificada_por->toBe($this->cajero->id)
        ->boleta_original_verificada_en->toEqual(now());
    Event::assertDispatchedTimes(PagoValidado::class);
});

it('refuses to validate without the original voucher and changes nothing', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: false))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Confirma que tienes la boleta original y que coincide con el número, la fecha y el monto registrados.');

    expect($pago->refresh())->estado->toBe(EstadoPago::Pendiente)->boleta_original_verificada_por->toBeNull();
    Event::assertNotDispatched(PagoValidado::class);
});

it('makes the database refuse a validated payment without the original voucher', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => DB::table('pag_pagos')->where('id', $pago->id)->update(['estado' => 'validado', 'validado_por' => $this->cajero->id, 'validado_en' => now()]))
        ->toThrow(QueryException::class, 'pag_pagos_boleta_original_check');
});

it('keeps the payments that existed before the change valid without inventing who checked their voucher', function (): void {
    $anterior = Pago::factory()->create();
    migracionDeBoletaOriginal()->down();
    DB::table('pag_pagos')->where('id', $anterior->id)->update(['estado' => 'validado', 'validado_por' => $this->cajero->id, 'validado_en' => now()]);

    migracionDeBoletaOriginal()->up();
    $nuevo = Pago::factory()->create();

    expect(DB::table('pag_pagos')->where('id', $anterior->id)->first(['exige_boleta_original', 'boleta_original_verificada_por']))
        ->exige_boleta_original->toBeFalse()
        ->boleta_original_verificada_por->toBeNull()
        ->and($nuevo->refresh()->exige_boleta_original)->toBeTrue();
});

it('asks for a justification to validate an amount different from the fee and keeps it', function (): void {
    $pago = pagoConTarifa('120.00', '150.00');

    expect(fn () => app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true, justificacionMonto: '   '))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El monto pagado (S/ 120.00) no coincide con la tarifa (S/ 150.00). Escribe la justificación para validarlo.')
        ->and($pago->refresh()->estado)->toBe(EstadoPago::Pendiente);

    app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true, justificacionMonto: ' Pagó con el descuento de hermanos. ');

    expect($pago->refresh())->estado->toBe(EstadoPago::Validado)->justificacion_monto->toBe('Pagó con el descuento de hermanos.');
});

it('validates an amount equal to the fee without a justification', function (): void {
    $pago = pagoConTarifa('150.00', '150.00');

    app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true);

    expect($pago->refresh())->estado->toBe(EstadoPago::Validado)->justificacion_monto->toBeNull();
});

it('does not let whoever registered a payment validate it, unless the CILN turns the rule off', function (bool $regla, bool $valida): void {
    config(['ciln.pagos.validacion_por_otra_persona' => $regla]);
    $pago = Pago::factory()->create(['registrado_por' => $this->cajero->id]);

    $validar = fn () => app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true);

    if ($valida) {
        $validar();
        expect($pago->refresh()->estado)->toBe(EstadoPago::Validado);

        return;
    }

    expect($validar)->toThrow(ReglaDeNegocioIncumplida::class, 'No puedes validar un pago que registraste tú; debe validarlo otra persona del CILN.')
        ->and($pago->refresh()->estado)->toBe(EstadoPago::Pendiente);
})->with([
    'rule on' => [true, false],
    'rule off' => [false, true],
]);

it('validates from the panel with the original voucher checkbox and the justification', function (): void {
    $pago = pagoConTarifa('120.00', '150.00');
    $this->actingAs($this->cajero);

    Livewire::test(ListPagos::class)
        ->callAction(TestAction::make('validar')->table($pago), data: ['boleta_original' => false])
        ->assertHasActionErrors(['boleta_original' => 'accepted']);

    Livewire::test(ListPagos::class)
        ->callAction(TestAction::make('validar')->table($pago), data: ['boleta_original' => true, 'justificacion_monto' => 'Pagó con el descuento de hermanos.'])
        ->assertHasNoActionErrors()
        ->assertNotified('Pago validado');

    expect($pago->refresh())->estado->toBe(EstadoPago::Validado)->boleta_original_verificada_por->toBe($this->cajero->id)->justificacion_monto->toBe('Pagó con el descuento de hermanos.');
});

it('warns the cashier about an amount different from the fee and an operation number used with another channel', function (): void {
    $pago = pagoConTarifa('120.00', '150.00');
    $pago->update(['numero_operacion' => '00012345']);
    Pago::factory()->create(['numero_operacion' => '00012345', 'medio_pago' => 'pagalo_pe']);
    $this->actingAs($this->cajero);

    Livewire::test(ViewPago::class, ['record' => $pago->getRouteKey()])
        ->assertSee('El monto pagado (S/ 120.00) no coincide con la tarifa (S/ 150.00).')
        ->assertSee('El número de operación 00012345 también figura en otro pago registrado con Págalo.pe.');
});

it('tells the student to bring the original voucher while the payment is pending', function (): void {
    $estudiante = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $estudiante->id]);
    Pago::factory()->create(['persona_id' => $persona->id]);

    $this->actingAs($estudiante)->get(route('estudiante.pagos'))
        ->assertOk()
        ->assertSee('Acércate al CILN con tu boleta original para validarla.');
});

it('refuses a justification longer than 500 characters', function (): void {
    $pago = pagoConTarifa('120.00', '150.00');

    expect(fn () => app(ValidarPago::class)($pago->id, $this->cajero, boletaOriginalVerificada: true, justificacionMonto: str_repeat('a', 501)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La justificación del monto no puede pasar de 500 caracteres.')
        ->and($pago->refresh()->estado)->toBe(EstadoPago::Pendiente);
});

it('leaves out a rejected payment from the other-channel warning and hides the section without alerts', function (): void {
    $pago = Pago::factory()->create(['numero_operacion' => '00012345']);
    Pago::factory()->rechazado()->create(['numero_operacion' => '00012345', 'medio_pago' => 'pagalo_pe']);
    $this->actingAs($this->cajero);

    Livewire::test(ViewPago::class, ['record' => $pago->getRouteKey()])
        ->assertDontSee('también figura en otro pago')
        ->assertDontSee('Revise antes de validar');
});

it('builds a validated payment whose voucher was checked by its validator, creating no extra user when both are given', function (): void {
    $usuarios = User::query()->count();

    $pago = Pago::factory()->validado()->create(['validado_por' => $this->cajero->id, 'boleta_original_verificada_por' => $this->cajero->id]);

    expect(User::query()->count())->toBe($usuarios)
        ->and(Pago::factory()->validado()->create())->validado_por->toBe(Pago::query()->latest('id')->value('boleta_original_verificada_por'));
});
