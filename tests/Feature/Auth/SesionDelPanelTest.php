<?php

declare(strict_types=1);

use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->personal = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->personal->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
});

function assertSalidaDelPanel(mixed $test, string $motivo): void
{
    $test->assertRedirect('/admin/login');
    test()->assertGuest();
    Notification::assertNotified($motivo);
}

it('uses the panel sign-in without "remember me", since a remembered session would skip the second factor', function (): void {
    expect(Filament::getPanel('admin')->getLoginRouteAction())->toBe(IngresarAlPanel::class);

    Livewire::test(IngresarAlPanel::class)->assertFormFieldHidden('remember');
});

it('starts the panel session only after the app code is accepted', function (): void {
    $proveedor = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders()['app'];

    $ingreso = Livewire::test(IngresarAlPanel::class)
        ->fillForm(['email' => 'caja@ciln.test', 'password' => 'password'])
        ->call('authenticate');

    expect(session()->has(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBeFalse();

    $ingreso->set('data.multiFactor.app.code', $proveedor->getCurrentCode($this->personal))->call('authenticate');

    $this->assertAuthenticatedAs($this->personal);
    expect(session(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBe(now()->getTimestamp());
});

it('refuses a session that was not opened by the panel sign-in and asks to sign in there', function (): void {
    $this->be($this->personal);

    assertSalidaDelPanel($this->get('/admin'), 'Ingresa al panel con tu correo, tu contraseña y el código de tu aplicación.');
});

it('sends staff who sign in on the portal to the panel sign-in', function (): void {
    Livewire::test(IniciarSesion::class)
        ->set('email', 'caja@ciln.test')
        ->set('password', 'password')
        ->call('ingresar')
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    assertSalidaDelPanel($this->get('/admin'), 'Ingresa al panel con tu correo, tu contraseña y el código de tu aplicación.');
});

it('closes the panel session after 30 minutes without activity, and each visit restarts the count', function (): void {
    $this->actingAs($this->personal);

    $this->travel(29)->minutes();
    $this->get('/admin')->assertOk();
    expect(session(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBe(now()->getTimestamp());

    $this->travel(29)->minutes();
    $this->get('/admin')->assertOk();

    $this->travel(31)->minutes();
    assertSalidaDelPanel($this->get('/admin'), 'Tu sesión del panel se cerró por inactividad. Vuelve a ingresar.');
});

it('takes the inactivity limit from the configuration', function (): void {
    config(['session.panel_lifetime' => 5]);
    $this->actingAs($this->personal);

    $this->travel(6)->minutes();

    assertSalidaDelPanel($this->get('/admin/profile'), 'Tu sesión del panel se cerró por inactividad. Vuelve a ingresar.');
});
