<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\GuardarConfiguracionDePagos;
use App\Modules\Pagos\Actions\RegistrarPagoAnteriorAlSistema;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Contracts\RegistroDePagoAnterior;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Filament\Pages\EditarConfiguracionDePagos;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->persona = Persona::factory()->create(['tipo_vinculo' => 'estudiante_unasam', 'habilitada_en' => null]);
    $this->concepto = Concepto::factory()->create();
    $this->tarifa = Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => 'estudiante_unasam', 'monto' => '120.00', 'vigente_desde' => '2026-01-01']);
    $this->personal = User::factory()->create()->givePermissionTo(PermisoPago::Registrar->value);
});

function pagoAnteriorAlSistema(array $cambios = [], string $motivo = '  Pago de noviembre de 2026, anterior al sistema ', ?UploadedFile $recibo = null): PagoAnteriorAlSistema
{
    return new PagoAnteriorAlSistema(new RegistroDePago(...[
        'personaId' => test()->persona->id,
        'conceptoId' => test()->concepto->id,
        'medioPagoId' => MedioDePago::idDe('banco_nacion'),
        'numeroOperacion' => ' nov-2026-001 ',
        'fechaPago' => CarbonImmutable::parse('2026-11-03'),
        'monto' => '120.00',
        ...$cambios,
    ]), $motivo, $recibo);
}

function registrarPagoAnterior(PagoAnteriorAlSistema $pago): Pago
{
    return Pago::query()->findOrFail(app(RegistrarPagoAnteriorAlSistema::class)($pago, test()->personal));
}

function permitirPagosAnteriores(bool $permitir): void
{
    $administrador = User::factory()->create()->givePermissionTo(PermisoPago::Configurar->value);
    app(GuardarConfiguracionDePagos::class)(['recargo_matricula_extemporanea' => 20, 'permite_pagos_anteriores_al_sistema' => $permitir], $administrador);
}

it('stores a payment made before the system as validated by the staff member, without a receipt file, and enables the account', function (): void {
    $pago = registrarPagoAnterior(pagoAnteriorAlSistema());

    expect($pago)
        ->estado->toBe(EstadoPago::Validado)
        ->anterior_al_sistema->toBeTrue()
        ->motivo_anterior->toBe('Pago de noviembre de 2026, anterior al sistema')
        ->recibo_path->toBeNull()
        ->recibo_sha256->toBeNull()
        ->numero_operacion->toBe('NOV-2026-001')
        ->tarifa_id->toBe($this->tarifa->id)
        ->registrado_por->toBe($this->personal->id)
        ->validado_por->toBe($this->personal->id)
        ->exige_boleta_original->toBeFalse()
        ->and($pago->validado_en)->not->toBeNull()
        ->and($this->persona->refresh()->habilitada_en)->not->toBeNull();
});

it('dispatches the validated payment event once the payment is stored', function (): void {
    Event::fake([PagoValidado::class]);

    $pago = registrarPagoAnterior(pagoAnteriorAlSistema());

    Event::assertDispatched(PagoValidado::class, fn (PagoValidado $evento): bool => $evento->pagoId === $pago->id);
});

it('stores the receipt file and its fingerprint when the staff member has it', function (): void {
    $contenido = "%PDF-1.4\n% anterior\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n";

    $pago = registrarPagoAnterior(pagoAnteriorAlSistema(recibo: UploadedFile::fake()->createWithContent('recibo.pdf', $contenido)));

    expect($pago->recibo_path)->toStartWith('recibos/2026/')->toEndWith('.pdf')
        ->and($pago->recibo_sha256)->toBe(hash('sha256', $contenido));
    Storage::disk('local')->assertExists((string) $pago->recibo_path);
});

it('requires the reason and keeps it within 500 characters', function (string $motivo, string $mensaje): void {
    expect(fn (): Pago => registrarPagoAnterior(pagoAnteriorAlSistema(motivo: $motivo)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect(Pago::query()->count())->toBe(0);
})->with([
    'blank' => ['   ', 'Escribe el motivo del pago anterior al sistema.'],
    'too long' => [str_repeat('a', 501), 'El motivo del pago anterior al sistema no puede pasar de 500 caracteres.'],
]);

it('refuses payments made before the system while the switch is off', function (): void {
    permitirPagosAnteriores(false);

    expect(fn (): Pago => registrarPagoAnterior(pagoAnteriorAlSistema()))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El registro de pagos anteriores al sistema está desactivado en la configuración de pagos.');
    expect(Pago::query()->count())->toBe(0);
});

it('applies the same checks as a normal registration', function (array $cambios, string $mensaje): void {
    Pago::factory()->create(['medio_pago_id' => MedioDePago::idDe('banco_nacion'), 'numero_operacion' => 'NOV-2026-001']);

    expect(fn (): Pago => registrarPagoAnterior(pagoAnteriorAlSistema($cambios)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'duplicated operation' => [[], 'Ese número de operación ya fue registrado con este medio de pago.'],
    'no fee on the date' => [['numeroOperacion' => 'OTRA-1', 'fechaPago' => CarbonImmutable::parse('2025-06-01')], 'No hay una tarifa vigente para este concepto en la fecha de pago.'],
]);

it('turns the switch on and off from the payment settings page', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(PermisoPago::Configurar->value));

    Livewire::test(EditarConfiguracionDePagos::class)
        ->assertSchemaStateSet(['permite_pagos_anteriores_al_sistema' => true])
        ->assertSee('Permitir pagos anteriores al sistema')
        ->fillForm(['permite_pagos_anteriores_al_sistema' => false])
        ->call('guardar')
        ->assertHasNoFormErrors();

    expect(fn (): Pago => registrarPagoAnterior(pagoAnteriorAlSistema()))->toThrow(ReglaDeNegocioIncumplida::class, 'está desactivado');
});

it('refuses in the database a payment without a receipt that is not from before the system', function (): void {
    $pago = Pago::factory()->make(['persona_id' => $this->persona->id, 'concepto_id' => $this->concepto->id, 'recibo_path' => null]);

    expect(fn () => DB::transaction(fn () => DB::table('pag_pagos')->insert([...$pago->getAttributes(), 'ulid' => (string) Str::ulid()])))
        ->toThrow(QueryException::class, 'pag_pagos_recibo_check');
});

it('refuses in the database a payment from before the system without a reason', function (): void {
    $pago = Pago::factory()->make(['persona_id' => $this->persona->id, 'concepto_id' => $this->concepto->id, 'recibo_path' => null]);

    expect(fn () => DB::transaction(fn () => DB::table('pag_pagos')->insert([...$pago->getAttributes(), 'ulid' => (string) Str::ulid(), 'anterior_al_sistema' => true, 'motivo_anterior' => ' '])))
        ->toThrow(QueryException::class, 'pag_pagos_motivo_anterior_check');
});

it('still refuses that the person who registered a normal payment validates it', function (): void {
    $pago = Pago::factory()->create(['persona_id' => $this->persona->id, 'registrado_por' => $this->personal->id]);

    expect(fn (): Pago => app(ValidarPago::class)($pago->id, $this->personal, true))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No puedes validar un pago que registraste tú; debe validarlo otra persona del CILN.');
});

it('lets only staff who register payments record a payment made before the system', function (): void {
    expect(fn () => app(RegistroDePagoAnterior::class)(pagoAnteriorAlSistema(), User::factory()->create()))->toThrow(AuthorizationException::class);
    expect(Pago::query()->count())->toBe(0);
});
