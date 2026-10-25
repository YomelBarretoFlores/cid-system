<?php

declare(strict_types=1);

use App\Shared\Auth\Filament\AutenticacionPorApp;
use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->proveedor = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders()['app'];
});

function personalDelPanel(bool $conDobleFactor): User
{
    $usuario = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    if ($conDobleFactor) {
        $usuario->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
    }

    return $usuario;
}

it('offers the authenticator app with recovery codes and a visible set-up button', function (): void {
    expect($this->proveedor)->toBeInstanceOf(AutenticacionPorApp::class)
        ->and($this->proveedor->isRecoverable())->toBeTrue()
        ->and($this->proveedor->getActions()[0]->getName())->toBe('setUpAppAuthentication')
        ->and($this->proveedor->getActions()[0]->isButton())->toBeTrue();
});

it('leaves the authenticator app optional by default, so an account without it reaches the panel', function (): void {
    $usuario = personalDelPanel(conDobleFactor: false);
    $this->be($usuario);

    $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => ProtegerSesionDelPanel::marca($usuario->id)])
        ->get('/admin')
        ->assertOk();
});

it('lets each person turn it on from the profile', function (): void {
    $usuario = personalDelPanel(conDobleFactor: false);
    $this->be($usuario);

    $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => ProtegerSesionDelPanel::marca($usuario->id)])
        ->get('/admin/profile')
        ->assertOk()
        ->assertSee('setUpAppAuthentication');
});

it('stores the secret and the recovery codes encrypted and never shows them', function (): void {
    $usuario = personalDelPanel(conDobleFactor: true);
    $codigos = $this->proveedor->generateRecoveryCodes();
    $this->proveedor->saveRecoveryCodes($usuario, $codigos);
    $fila = DB::table('users')->where('id', $usuario->id)->first();
    $guardados = json_decode(decrypt($fila->app_authentication_recovery_codes, unserialize: false), true);

    expect($fila->app_authentication_secret)->not->toBe($usuario->getAppAuthenticationSecret())
        ->and(decrypt($fila->app_authentication_secret, unserialize: false))->toBe($usuario->getAppAuthenticationSecret())
        ->and($codigos)->toHaveCount(8)
        ->and($guardados)->toHaveCount(8)
        ->and(Hash::check($codigos[0], $guardados[0]))->toBeTrue()
        ->and($fila->app_authentication_recovery_codes)->not->toContain($codigos[0])
        ->and($usuario->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
});

it('sends a panel account without the authenticator app to set it up first when the setting makes it required', function (): void {
    config(['auth.panel_doble_factor_obligatorio' => true]);
    $usuario = personalDelPanel(conDobleFactor: false);
    $this->be($usuario);

    $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => ProtegerSesionDelPanel::marca($usuario->id)])
        ->get('/admin')
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
});

it('asks for the app code after the password and signs in only with a valid one', function (): void {
    $usuario = personalDelPanel(conDobleFactor: true);

    $ingreso = Livewire::test(Login::class)
        ->fillForm(['email' => 'caja@ciln.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertSet('userUndertakingMultiFactorAuthentication', fn (?string $valor): bool => filled($valor));

    $this->assertGuest();

    $codigo = $this->proveedor->getCurrentCode($usuario);
    $equivocado = substr($codigo, 0, 5).(((int) $codigo[5] + 1) % 10);

    $ingreso->set('data.multiFactor.app.code', $equivocado)->call('authenticate')->assertHasErrors();
    $this->assertGuest();

    $ingreso->set('data.multiFactor.app.code', $codigo)->call('authenticate')->assertHasNoErrors();
    $this->assertAuthenticatedAs($usuario);
});

it('signs in once with each recovery code when the phone is lost', function (): void {
    $this->freezeSecond();
    $usuario = personalDelPanel(conDobleFactor: true);
    $codigos = $this->proveedor->generateRecoveryCodes();
    $this->proveedor->saveRecoveryCodes($usuario, $codigos);

    $ingresarCon = fn (string $codigo) => Livewire::test(IngresarAlPanel::class)
        ->fillForm(['email' => 'caja@ciln.test', 'password' => 'password'])
        ->call('authenticate')
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', $codigo)
        ->call('authenticate');

    $ingresarCon($codigos[0])->assertHasNoErrors();
    $this->assertAuthenticatedAs($usuario);
    expect(session(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBe(ProtegerSesionDelPanel::marca($usuario->id));
    expect($usuario->fresh()->getAppAuthenticationRecoveryCodes())->toHaveCount(7);

    auth()->logout();
    $ingresarCon($codigos[0])->assertHasErrors();
    $this->assertGuest();
});
