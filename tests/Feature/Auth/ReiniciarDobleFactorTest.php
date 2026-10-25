<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;

it('clears the second factor of an account that lost its phone and its recovery codes', function (): void {
    $usuario = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $usuario->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    $usuario->saveAppAuthenticationRecoveryCodes(['codigo']);

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => ' CAJA@ciln.test '])
        ->expectsOutput('Se quitó el doble factor de la cuenta. Deberá configurarlo de nuevo al ingresar al panel.')
        ->assertSuccessful();

    expect($usuario->fresh())
        ->getAppAuthenticationSecret()->toBeNull()
        ->getAppAuthenticationRecoveryCodes()->toBeNull();
});

it('fails without changing anything when no account has that e-mail', function (): void {
    $usuario = User::factory()->create(['email' => 'caja@ciln.test']);
    $usuario->saveAppAuthenticationSecret('SECRETO');

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'otra@ciln.test'])
        ->expectsOutput('No hay una cuenta con ese correo.')
        ->assertFailed();

    expect($usuario->fresh()->getAppAuthenticationSecret())->toBe('SECRETO');
});
