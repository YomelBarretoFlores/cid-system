<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

it('clears the second factor of an account that lost its phone and its recovery codes, and ends its sessions', function (): void {
    $usuario = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $usuario->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    $usuario->saveAppAuthenticationRecoveryCodes(['codigo']);
    $otra = User::factory()->create();
    DB::table('sessions')->insert([
        ['id' => 'sesion-de-caja', 'user_id' => $usuario->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
        ['id' => 'sesion-de-otra', 'user_id' => $otra->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
    ]);
    $recordarAntes = $usuario->remember_token;
    config(['session.driver' => 'database']);
    Log::spy();

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => ' CAJA@ciln.test '])
        ->expectsOutput('Se quitó el doble factor de la cuenta. Deberá configurarlo de nuevo al ingresar al panel.')
        ->assertSuccessful();

    expect($usuario->fresh())
        ->getAppAuthenticationSecret()->toBeNull()
        ->getAppAuthenticationRecoveryCodes()->toBeNull()
        ->remember_token->not->toBe($recordarAntes)
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['sesion-de-otra']);

    Log::shouldHaveReceived('info')->once()->with('Se reinició el doble factor de una cuenta.', ['usuario_id' => $usuario->id]);
});

it('fails without changing anything when no account has that e-mail', function (): void {
    $usuario = User::factory()->create(['email' => 'caja@ciln.test']);
    $usuario->saveAppAuthenticationSecret('SECRETO');

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'otra@ciln.test'])
        ->expectsOutput('No hay una cuenta con ese correo.')
        ->assertFailed();

    expect($usuario->fresh()->getAppAuthenticationSecret())->toBe('SECRETO');
});
