<?php

declare(strict_types=1);

use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

function docenteConUsuario(string $usuario, ?string $correo = null): User
{
    [$cuenta] = PortalDocente::docente();
    $cuenta->forceFill(['usuario' => $usuario, 'email' => $correo])->save();

    return $cuenta;
}

it('signs a teacher in with the DNI as username, without an email on the account', function (): void {
    $docente = docenteConUsuario('41234567');

    Livewire::test(IniciarSesion::class)
        ->set('email', ' 41234567 ')
        ->set('password', 'password')
        ->call('ingresar')
        ->assertHasNoErrors()
        ->assertRedirect(route('filament.docente.pages.mis-grupos'));

    $this->assertAuthenticatedAs($docente);
});

it('rejects a wrong password for a username with the generic message', function (): void {
    docenteConUsuario('41234567');

    Livewire::test(IniciarSesion::class)
        ->set('email', '41234567')
        ->set('password', 'otra-clave1')
        ->call('ingresar')
        ->assertHasErrors(['email' => 'El usuario o la contraseña no son correctos.']);

    $this->assertGuest();
});

it('still signs in with the email an account with a username also has', function (): void {
    $docente = docenteConUsuario('41234567', 'docente@correo.test');

    Livewire::test(IniciarSesion::class)
        ->set('email', 'Docente@correo.test')
        ->set('password', 'password')
        ->call('ingresar')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($docente);
});

it('labels the sign-in field for the username or the email', function (): void {
    $this->get(route('ingresar'))->assertOk()->assertSee('Usuario o correo');
});

it('issues an API token for a username', function (): void {
    $docente = docenteConUsuario('41234567');

    $respuesta = $this->postJson('/api/v1/sesiones', ['email' => '41234567', 'password' => 'password', 'dispositivo' => 'App móvil'])
        ->assertCreated()
        ->assertJsonPath('perfiles.docente', true);

    expect(PersonalAccessToken::findToken((string) $respuesta->json('token'))?->tokenable_id)->toBe($docente->id);
});

it('keeps the username unique', function (): void {
    docenteConUsuario('41234567');

    expect(fn () => User::factory()->create(['usuario' => '41234567']))->toThrow(QueryException::class, 'users_usuario_unique');
});

it('requires a username or an email on every account', function (): void {
    expect(fn () => User::factory()->create(['email' => null]))->toThrow(QueryException::class, 'users_usuario_o_correo');
});
