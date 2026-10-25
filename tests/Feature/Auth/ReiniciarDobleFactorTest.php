<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

const REINICIO_HECHO = 'Se quitó el doble factor de la cuenta. Deberá configurarlo de nuevo al ingresar al panel.';
const SESIONES_CERRADAS = 'Se cerraron las sesiones abiertas de la cuenta.';
const SESIONES_SIN_CUENTA = 'Las sesiones no se guardan en la base de datos (SESSION_DRIVER), así que no se pueden cerrar por cuenta. Expirarán solas o al reiniciar el almacenamiento de sesiones.';
const SESIONES_CON_FALLA = 'No se pudieron cerrar las sesiones abiertas de la cuenta. Cuando la conexión vuelva, ejecuta el comando con --solo-sesiones: cierra las sesiones sin volver a quitar el doble factor.';

beforeEach(function (): void {
    config(['session.driver' => 'database']);
    $this->usuario = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->usuario->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    $this->usuario->saveAppAuthenticationRecoveryCodes(['codigo']);
    $this->otra = User::factory()->create();
    DB::table('sessions')->insert([
        ['id' => 'sesion-de-caja', 'user_id' => $this->usuario->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
        ['id' => 'sesion-de-otra', 'user_id' => $this->otra->id, 'payload' => '', 'last_activity' => now()->getTimestamp()],
    ]);
    $this->recordarAntes = $this->usuario->remember_token;
    Log::spy();
});

it('clears the second factor of an account that lost its phone and its recovery codes, and ends its sessions', function (): void {
    $this->artisan('auth:reiniciar-doble-factor', ['correo' => ' CAJA@ciln.test '])
        ->expectsOutput(REINICIO_HECHO)
        ->expectsOutput(SESIONES_CERRADAS)
        ->doesntExpectOutputToContain('No se pudieron cerrar')
        ->assertExitCode(0);

    expect($this->usuario->fresh())
        ->getAppAuthenticationSecret()->toBeNull()
        ->getAppAuthenticationRecoveryCodes()->toBeNull()
        ->remember_token->not->toBe($this->recordarAntes)
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['sesion-de-otra']);

    Log::shouldHaveReceived('info')->with('Se reinició el doble factor de una cuenta.', ['usuario_id' => $this->usuario->id]);
    Log::shouldHaveReceived('info')->with('Se cerraron las sesiones de una cuenta.', ['usuario_id' => $this->usuario->id]);
});

it('closes only the sessions with --solo-sesiones, keeping a factor configured again', function (): void {
    $secreto = $this->usuario->getAppAuthenticationSecret();

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'caja@ciln.test', '--solo-sesiones' => true])
        ->expectsOutput(SESIONES_CERRADAS)
        ->doesntExpectOutput(REINICIO_HECHO)
        ->assertExitCode(0);

    expect($this->usuario->fresh())
        ->getAppAuthenticationSecret()->toBe($secreto)
        ->getAppAuthenticationRecoveryCodes()->toBe(['codigo'])
        ->remember_token->not->toBe($this->recordarAntes)
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['sesion-de-otra']);

    Log::shouldNotHaveReceived('info', ['Se reinició el doble factor de una cuenta.', ['usuario_id' => $this->usuario->id]]);
});

it('keeps the reset, logs it and exits with code 2 when the sessions cannot be deleted', function (): void {
    config(['session.connection' => 'conexion_inexistente']);

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'caja@ciln.test'])
        ->expectsOutput(REINICIO_HECHO)
        ->expectsOutput(SESIONES_CON_FALLA)
        ->assertExitCode(2);

    expect($this->usuario->fresh()->getAppAuthenticationSecret())->toBeNull()
        ->and(DB::table('sessions')->count())->toBe(2);
    Log::shouldHaveReceived('info')->with('Se reinició el doble factor de una cuenta.', ['usuario_id' => $this->usuario->id]);
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $mensaje, array $contexto): bool => $mensaje === 'No se pudieron cerrar las sesiones de una cuenta.'
        && $contexto['usuario_id'] === $this->usuario->id
        && $contexto['causa'] === InvalidArgumentException::class);
});

it('keeps the reset, logs it and exits with code 2 when sessions are not stored in the database', function (): void {
    config(['session.driver' => 'file']);

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'caja@ciln.test'])
        ->expectsOutput(REINICIO_HECHO)
        ->expectsOutput(SESIONES_SIN_CUENTA)
        ->doesntExpectOutputToContain('--solo-sesiones')
        ->assertExitCode(2);

    expect($this->usuario->fresh())
        ->getAppAuthenticationSecret()->toBeNull()
        ->remember_token->not->toBe($this->recordarAntes)
        ->and(DB::table('sessions')->count())->toBe(2);
    Log::shouldHaveReceived('warning')->once()->with('No se pudieron cerrar las sesiones de una cuenta.', ['usuario_id' => $this->usuario->id, 'causa' => 'almacenamiento_sin_cuentas']);
});

it('reports open sessions with code 2 for --solo-sesiones too when sessions are not stored in the database', function (): void {
    config(['session.driver' => 'file']);

    $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'caja@ciln.test', '--solo-sesiones' => true])
        ->expectsOutput(SESIONES_SIN_CUENTA)
        ->assertExitCode(2);

    expect($this->usuario->fresh()->remember_token)->not->toBe($this->recordarAntes);
});

it('fails with code 1 without changing anything when no account has that e-mail', function (): void {
    $secreto = $this->usuario->getAppAuthenticationSecret();

    foreach ([[], ['--solo-sesiones' => true]] as $opciones) {
        $this->artisan('auth:reiniciar-doble-factor', ['correo' => 'otra@ciln.test', ...$opciones])
            ->expectsOutput('No hay una cuenta con ese correo.')
            ->assertExitCode(1);
    }

    expect($this->usuario->fresh()->getAppAuthenticationSecret())->toBe($secreto)
        ->and(DB::table('sessions')->count())->toBe(2);
});
