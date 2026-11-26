<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\Docente;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Livewire\Registro;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function cuentaSinHabilitar(): User
{
    $user = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $user->id, 'habilitada_en' => null]);

    return $user;
}

function registrarCuenta(array $cambios = []): Testable
{
    return Livewire::test(Registro::class)->fill([
        'tipo_documento' => 'dni', 'numero_documento' => '41234567', 'nombres' => 'Rosa Elena',
        'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mendoza', 'fecha_nacimiento' => '2000-05-10',
        'email' => 'rosa@correo.test', 'celular' => '987654321', 'password' => 'clave-segura1',
        'password_confirmation' => 'clave-segura1', 'acepta_tratamiento' => true, ...$cambios,
    ])->call('registrarse');
}

dataset('secciones genericas', [
    'inicio' => fn (): string => route('estudiante.inicio'),
    'datos' => fn (): string => route('estudiante.perfil.datos'),
    'foto' => fn (): string => route('estudiante.perfil.foto'),
    'preferencias' => fn (): string => route('estudiante.preferencias'),
    'pagos' => fn (): string => route('estudiante.pagos'),
]);

dataset('secciones habilitadas', [
    'matricula' => fn (): string => route('estudiante.matricula'),
    'matriculas' => fn (): string => route('estudiante.matriculas'),
    'clasificacion' => fn (): string => route('estudiante.clasificacion'),
    'reportes' => fn (): string => route('estudiante.reportes'),
    'tramites' => fn (): string => route('estudiante.tramites'),
]);

it('signs a new account in straight to the portal without sending a verification email', function (): void {
    Notification::fake();

    registrarCuenta()->assertHasNoErrors()->assertRedirect(route('estudiante.inicio'));

    expect(Persona::query()->sole()->habilitada_en)->toBeNull();
    Notification::assertNothingSent();
});

it('does not let a registration take over the record of a teacher on file', function (): void {
    $persona = Persona::factory()->create(['numero_documento' => '41234567', 'correo' => 'rosa@correo.test']);
    Docente::factory()->create(['persona_id' => $persona->id]);

    registrarCuenta()->assertHasErrors('numero_documento');

    expect($persona->fresh()->user_id)->toBeNull();
    $this->assertGuest();
});

it('lets an account not yet enabled open the generic sections', function (string $ruta): void {
    $this->actingAs(cuentaSinHabilitar())->get($ruta)->assertOk();
})->with('secciones genericas');

it('sends an account not yet enabled from the other sections back to the home page', function (string $ruta): void {
    $this->actingAs(cuentaSinHabilitar())->get($ruta)->assertRedirect(route('estudiante.inicio'));
})->with('secciones habilitadas');

it('tells an account not yet enabled how to enable it and hides the other sections from the menu', function (): void {
    $this->actingAs(cuentaSinHabilitar())->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Tu cuenta aún no está habilitada')
        ->assertSee(route('estudiante.pagos'))
        ->assertDontSee(route('estudiante.matricula'))
        ->assertDontSee(route('estudiante.tramites'));
});

it('refuses the API data of the other sections to an account not yet enabled but still shows its profile and payments', function (): void {
    Sanctum::actingAs(cuentaSinHabilitar());

    $this->getJson(route('api.estudiante.perfil'))->assertOk();
    $this->getJson(route('api.estudiante.pagos'))->assertOk();
    $this->getJson(route('api.estudiante.matriculas'))->assertForbidden()
        ->assertJsonPath('message', 'Tu cuenta aún no está habilitada. Acércate al CILN con tu recibo de pago.');
});

it('enables the account when the cashier validates its first payment, and not when a payment is rejected', function (): void {
    $user = cuentaSinHabilitar();
    $persona = Persona::query()->where('user_id', $user->id)->sole();
    $cajero = User::factory()->create()->givePermissionTo(['pagos.validar']);
    $rechazado = Pago::factory()->pendiente()->create(['persona_id' => $persona->id]);
    $valido = Pago::factory()->pendiente()->create(['persona_id' => $persona->id]);

    app(RechazarPago::class)($rechazado->id, $cajero, 'El número de operación no coincide con el voucher.');
    expect($persona->fresh()->habilitada_en)->toBeNull();

    $this->freezeSecond();
    app(ValidarPago::class)($valido->id, $cajero, boletaOriginalVerificada: true);

    expect($persona->fresh()->habilitada_en)->toEqual(now());
    $this->actingAs($user)->get(route('estudiante.matricula'))->assertOk();
    $this->get(route('estudiante.inicio'))->assertDontSee('Tu cuenta aún no está habilitada')->assertSee(route('estudiante.tramites'));
});

it('treats records registered by staff as enabled', function (): void {
    expect(Persona::factory()->create()->fresh()->habilitada_en)->not->toBeNull();
});

it('no longer asks students or teachers to verify their email', function (): void {
    $user = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.matricula'))->assertOk();
    $this->get('/verificar-correo')->assertNotFound();
});
