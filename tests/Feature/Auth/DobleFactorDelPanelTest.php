<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
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

it('offers the authenticator app with recovery codes and requires it for every panel account', function (): void {
    expect($this->proveedor)->toBeInstanceOf(AppAuthentication::class)
        ->and($this->proveedor->isRecoverable())->toBeTrue()
        ->and(Filament::getPanel('admin')->isMultiFactorAuthenticationRequired())->toBeTrue();
});

it('stores the secret and the recovery codes encrypted and never shows them', function (): void {
    $usuario = personalDelPanel(conDobleFactor: true);
    $usuario->saveAppAuthenticationRecoveryCodes(['codigo-uno', 'codigo-dos']);
    $fila = DB::table('users')->where('id', $usuario->id)->first();

    expect($fila->app_authentication_secret)->not->toBe($usuario->getAppAuthenticationSecret())
        ->and(decrypt($fila->app_authentication_secret, unserialize: false))->toBe($usuario->getAppAuthenticationSecret())
        ->and($fila->app_authentication_recovery_codes)->not->toContain('codigo-uno')
        ->and($usuario->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
});

it('sends a panel account without the authenticator app to set it up first', function (): void {
    $this->be(personalDelPanel(conDobleFactor: false));

    $this->get('/admin')
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
});

it('asks for the app code after the password and signs in only with a valid one', function (): void {
    $usuario = personalDelPanel(conDobleFactor: true);

    $ingreso = Livewire::test(Login::class)
        ->fillForm(['email' => 'caja@ciln.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertSet('userUndertakingMultiFactorAuthentication', fn (?string $valor): bool => filled($valor));

    $this->assertGuest();

    $ingreso->set('data.multiFactor.app.code', '000000')->call('authenticate')->assertHasErrors();
    $this->assertGuest();

    $ingreso->set('data.multiFactor.app.code', $this->proveedor->getCurrentCode($usuario))->call('authenticate')->assertHasNoErrors();
    $this->assertAuthenticatedAs($usuario);
});
