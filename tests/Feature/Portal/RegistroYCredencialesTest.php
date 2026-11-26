<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Livewire\Registro;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Livewire\RestablecerClave;
use App\Shared\Auth\Livewire\SolicitarRestablecimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

/**
 * @param  array<string, mixed>  $cambios
 */
function sondaRegistrarse(array $cambios = []): Testable
{
    $componente = Livewire::test(Registro::class);

    foreach ([
        'tipo_documento' => 'dni', 'numero_documento' => '45678912', 'apellido_paterno' => 'Rojas',
        'apellido_materno' => 'Lopez', 'nombres' => 'Ana', 'fecha_nacimiento' => '2000-01-10',
        'email' => 'ana@correo.test', 'celular' => '987654321', 'password' => 'clave-segura1',
        'password_confirmation' => 'clave-segura1', 'acepta_tratamiento' => true, ...$cambios,
    ] as $campo => $valor) {
        $componente->set($campo, $valor);
    }

    return $componente->call('registrarse');
}

/** @return array{int, int} */
function sondaConteos(): array
{
    return [User::query()->count(), Persona::query()->count()];
}

it('cannot be tampered into another link type', function (): void {
    expect(fn () => Livewire::test(Registro::class)->set('tipo_vinculo', 'estudiante_unasam'))->toThrow(Exception::class);

    sondaRegistrarse()->assertHasNoErrors();

    expect(Persona::query()->sole()->tipo_vinculo)->toBe('publico_general');
});

it('refuses a record on file with another email and one with an account using the same message, creating nothing', function (): void {
    Persona::factory()->create(['tipo_documento' => TipoDocumento::Dni, 'numero_documento' => '45678912', 'correo' => 'otro@correo.test']);
    Persona::factory()->create(['tipo_documento' => TipoDocumento::Dni, 'numero_documento' => '45678913', 'correo' => 'ana2@correo.test', 'user_id' => User::factory()]);
    $antes = sondaConteos();

    sondaRegistrarse()->assertHasErrors(['numero_documento' => RegistrarCuentaDeEstudiante::DOCUMENTO_NO_DISPONIBLE]);
    sondaRegistrarse(['numero_documento' => '45678913', 'email' => 'ana2@correo.test'])->assertHasErrors(['numero_documento' => RegistrarCuentaDeEstudiante::DOCUMENTO_NO_DISPONIBLE]);

    expect(sondaConteos())->toBe($antes)->and(auth()->check())->toBeFalse();
});

it('links a record on file with the same email and keeps its link type', function (): void {
    $persona = Persona::factory()->estudianteUnasam()->create(['tipo_documento' => TipoDocumento::Dni, 'numero_documento' => '45678912', 'correo' => 'ana@correo.test']);

    sondaRegistrarse(['email' => '  ANA@Correo.test '])->assertHasNoErrors()->assertRedirect(route('estudiante.inicio'));

    expect($persona->refresh()->user_id)->toBe(User::query()->sole()->id)
        ->and($persona->tipo_vinculo)->toBe('estudiante_unasam')
        ->and(Persona::query()->count())->toBe(1);
    Notification::assertNothingSent();
});

it('requires consent and creates nothing without it', function (): void {
    sondaRegistrarse(['acepta_tratamiento' => false])->assertHasErrors(['acepta_tratamiento' => 'accepted']);

    expect(sondaConteos())->toBe([0, 0]);
});

it('limits registrations to 20 per minute per address', function (): void {
    foreach (range(1, 20) as $intento) {
        sondaRegistrarse(['acepta_tratamiento' => false]);
    }

    sondaRegistrarse()->assertHasErrors('registro');

    expect(sondaConteos())->toBe([0, 0]);
});

it('limits login to 5 attempts per email and address even with the right password', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test', 'password' => 'clave-segura1']);

    foreach (range(1, 5) as $intento) {
        Livewire::test(IniciarSesion::class)->set('email', 'ana@correo.test')->set('password', 'mala-clave1')->call('ingresar')
            ->assertHasErrors(['email' => IniciarSesion::CREDENCIALES_INCORRECTAS]);
    }

    Livewire::test(IniciarSesion::class)->set('email', 'ana@correo.test')->set('password', 'clave-segura1')->call('ingresar')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse()->and($user->exists)->toBeTrue();
});

it('limits login to 20 attempts per address across accounts', function (): void {
    User::factory()->create(['email' => 'ana@correo.test', 'password' => 'clave-segura1']);

    foreach (range(1, 20) as $intento) {
        Livewire::test(IniciarSesion::class)->set('email', "nadie{$intento}@correo.test")->set('password', 'mala-clave1')->call('ingresar');
    }

    Livewire::test(IniciarSesion::class)->set('email', 'ana@correo.test')->set('password', 'clave-segura1')->call('ingresar')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('limits reset requests to 5 per minute per address', function (): void {
    User::factory()->create(['email' => 'ana@correo.test']);

    foreach (range(1, 5) as $intento) {
        Livewire::test(SolicitarRestablecimiento::class)->set('email', "x{$intento}@correo.test")->call('enviar')->assertHasNoErrors();
    }

    Livewire::test(SolicitarRestablecimiento::class)->set('email', 'ana@correo.test')->call('enviar')->assertHasErrors('email');

    Notification::assertNothingSent();
});

it('refuses an invalid or expired reset token and keeps the password', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test', 'password' => 'clave-segura1']);

    Livewire::test(RestablecerClave::class, ['token' => 'falso'])
        ->set('email', 'ana@correo.test')->set('password', 'nueva-clave2')->set('password_confirmation', 'nueva-clave2')
        ->call('restablecer')->assertHasErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);

    $token = Password::createToken($user);
    $this->travel(61)->minutes();

    Livewire::test(RestablecerClave::class, ['token' => $token])
        ->set('email', 'ana@correo.test')->set('password', 'nueva-clave2')->set('password_confirmation', 'nueva-clave2')
        ->call('restablecer')->assertHasErrors('email');

    expect(Hash::check('clave-segura1', $user->refresh()->password))->toBeTrue();
    Notification::assertNotSentTo($user, CorreoDeRestablecimiento::class);
});

it('resets with a valid token and rotates the remember token', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test', 'password' => 'clave-segura1', 'remember_token' => 'viejo']);
    $token = Password::createToken($user);

    Livewire::test(RestablecerClave::class, ['token' => $token])
        ->set('email', 'ANA@correo.test')->set('password', 'nueva-clave2')->set('password_confirmation', 'nueva-clave2')
        ->call('restablecer')->assertHasNoErrors()->assertRedirect(route('ingresar'));

    expect(Hash::check('nueva-clave2', $user->refresh()->password))->toBeTrue()->and($user->remember_token)->not->toBe('viejo');
});
