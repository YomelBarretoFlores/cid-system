<?php

declare(strict_types=1);

use App\Shared\Auth\Livewire\RestablecerClave;
use App\Shared\Auth\Livewire\SolicitarRestablecimiento;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

const AVISO_DE_ENVIO = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';

beforeEach(fn () => Notification::fake());

it('emails a reset link in Spanish to a registered address', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test']);

    Livewire::test(SolicitarRestablecimiento::class)
        ->set('email', 'ana@correo.test')
        ->call('enviar')
        ->assertHasNoErrors()
        ->assertSee(AVISO_DE_ENVIO);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $aviso) use ($user): bool {
        $correo = $aviso->toMail($user);

        return $correo->subject === 'Restablece tu contraseña del CILN'
            && str_contains((string) $correo->actionUrl, route('password.reset', ['token' => $aviso->token], false));
    });
});

it('answers the same for an unknown address and sends nothing', function (): void {
    Livewire::test(SolicitarRestablecimiento::class)
        ->set('email', 'nadie@correo.test')
        ->call('enviar')
        ->assertHasNoErrors()
        ->assertSee(AVISO_DE_ENVIO);

    Notification::assertNothingSent();
});

it('sets the new password with a valid token and returns to the login', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test']);
    $token = Password::createToken($user);

    Livewire::test(RestablecerClave::class, ['token' => $token])
        ->set('email', 'ana@correo.test')
        ->set('password', 'nueva-clave9')
        ->set('password_confirmation', 'nueva-clave9')
        ->call('restablecer')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.ingresar'));

    expect(Hash::check('nueva-clave9', $user->refresh()->password))->toBeTrue();
});

it('refuses an invalid token and keeps the old password', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test']);

    Livewire::test(RestablecerClave::class, ['token' => 'token-falso'])
        ->set('email', 'ana@correo.test')
        ->set('password', 'nueva-clave9')
        ->set('password_confirmation', 'nueva-clave9')
        ->call('restablecer')
        ->assertHasErrors(['email' => 'El enlace no es válido o ya venció. Solicita uno nuevo.']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('requires a password with letters and numbers', function (string $clave, string $mensaje): void {
    $user = User::factory()->create();

    Livewire::test(RestablecerClave::class, ['token' => Password::createToken($user)])
        ->set('email', $user->email)
        ->set('password', $clave)
        ->set('password_confirmation', $clave)
        ->call('restablecer')
        ->assertHasErrors(['password' => $mensaje]);
})->with([
    'too short' => ['abc12', 'La contraseña debe tener al menos 8 caracteres.'],
    'no numbers' => ['solo-letras', 'La contraseña debe incluir al menos una letra y un número.'],
    'no letters' => ['12345678', 'La contraseña debe incluir al menos una letra y un número.'],
]);

it('limits reset requests from the same address to five a minute', function (): void {
    foreach (range(1, 5) as $intento) {
        Livewire::test(SolicitarRestablecimiento::class)->set('email', "persona{$intento}@correo.test")->call('enviar')->assertHasNoErrors();
    }

    $componente = Livewire::test(SolicitarRestablecimiento::class)->set('email', 'otra@correo.test')->call('enviar');

    expect($componente->errors()->first('email'))->toStartWith('Demasiadas solicitudes. Vuelve a intentarlo en');
});
