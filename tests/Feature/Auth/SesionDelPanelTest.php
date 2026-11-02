<?php

declare(strict_types=1);

use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->personal = User::factory()->create(['email' => 'caja@ciln.test'])->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->personal->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
});

// Livewire::test skips the persistent middleware, so these go through the real update endpoint.
function accionDelPerfil(TestResponse $pagina): TestResponse
{
    // Each production request starts clean; in one test Livewire would skip the middleware it already ran.
    Livewire::flushState();
    preg_match('/wire:snapshot="([^"]*EditarPerfil[^"]*)"/', $pagina->getContent(), $coincidencia);

    return test()->withHeaders(['X-Livewire' => '1'])->postJson(route('default-livewire.update'), ['components' => [[
        'snapshot' => html_entity_decode($coincidencia[1], ENT_QUOTES),
        'updates' => [],
        'calls' => [['method' => '$refresh', 'params' => [], 'metadata' => []]],
    ]]]);
}

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
    $this->freezeSecond();
    $proveedor = Filament::getPanel('admin')->getMultiFactorAuthenticationProviders()['app'];

    $ingreso = Livewire::test(IngresarAlPanel::class)
        ->fillForm(['email' => 'caja@ciln.test', 'password' => 'password'])
        ->call('authenticate');

    expect(session()->has(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBeFalse();

    $ingreso->set('data.multiFactor.app.code', $proveedor->getCurrentCode($this->personal))->call('authenticate');

    $this->assertAuthenticatedAs($this->personal);
    expect(session(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBe(ProtegerSesionDelPanel::marca($this->personal->id));
});

it('refuses a session that was not opened by the panel sign-in and asks to sign in there', function (): void {
    $this->be($this->personal);
    Log::spy();

    assertSalidaDelPanel($this->get('/admin'), ProtegerSesionDelPanel::AVISO_SIN_INGRESO);
    Log::shouldHaveReceived('info')->with('Se cerró una sesión del panel.', ['usuario_id' => $this->personal->id, 'causa' => 'sin_ingreso_del_panel']);
});

it('sends staff who sign in on the portal to the panel sign-in', function (): void {
    Livewire::test(IniciarSesion::class)
        ->set('email', 'caja@ciln.test')
        ->set('password', 'password')
        ->call('ingresar')
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    assertSalidaDelPanel($this->get('/admin'), ProtegerSesionDelPanel::AVISO_SIN_INGRESO);
});

it('closes the panel session after 30 minutes without activity, and each visit restarts the count', function (): void {
    $this->actingAs($this->personal);

    $this->travel(29)->minutes();
    $this->get('/admin')->assertOk();
    expect(session(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD))->toBe(ProtegerSesionDelPanel::marca($this->personal->id));

    $this->travel(30)->minutes();
    $this->get('/admin')->assertOk();

    $this->travel(31)->minutes();
    Log::spy();
    assertSalidaDelPanel($this->get('/admin'), ProtegerSesionDelPanel::AVISO_INACTIVIDAD);
    Log::shouldHaveReceived('info')->with('Se cerró una sesión del panel.', ['usuario_id' => $this->personal->id, 'causa' => 'inactividad']);
});

it('refuses the panel mark of another account signed in earlier in the same browser', function (): void {
    $otraCuenta = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->be($this->personal);

    $respuesta = $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => ProtegerSesionDelPanel::marca($otraCuenta->id)])->get('/admin');

    assertSalidaDelPanel($respuesta, ProtegerSesionDelPanel::AVISO_SIN_INGRESO);
});

it('counts panel actions as activity and closes the session on an action after the limit', function (): void {
    $this->actingAs($this->personal);
    $pagina = $this->get('/admin/profile')->assertOk();

    $this->travel(20)->minutes();
    accionDelPerfil($pagina)->assertOk();
    $this->travel(20)->minutes();
    $this->get('/admin')->assertOk();

    $this->travel(31)->minutes();
    assertSalidaDelPanel(accionDelPerfil($pagina), ProtegerSesionDelPanel::AVISO_INACTIVIDAD);
});

it('refuses a panel action from a session without the panel mark', function (): void {
    $this->actingAs($this->personal);
    $pagina = $this->get('/admin/profile')->assertOk();
    session()->forget(ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD);

    assertSalidaDelPanel(accionDelPerfil($pagina), ProtegerSesionDelPanel::AVISO_SIN_INGRESO);
});

it('refuses a session restored by a "remember me" cookie', function (): void {
    $cuenta = $this->personal->fresh();
    $cookie = $cuenta->id.'|'.$cuenta->remember_token.'|'.$cuenta->password;

    assertSalidaDelPanel($this->withCookie(auth()->guard('web')->getRecallerName(), $cookie)->get('/admin'), ProtegerSesionDelPanel::AVISO_SIN_INGRESO);
});

it('takes the inactivity limit from the configuration, never below one minute', function (): void {
    config(['session.panel_lifetime' => 0]);
    $this->actingAs($this->personal);

    $this->travel(59)->seconds();
    $this->get('/admin')->assertOk();

    $this->travel(61)->seconds();
    assertSalidaDelPanel($this->get('/admin'), ProtegerSesionDelPanel::AVISO_INACTIVIDAD);
});

it('takes the inactivity limit from the configuration', function (): void {
    config(['session.panel_lifetime' => 5]);
    $this->actingAs($this->personal);

    $this->travel(6)->minutes();

    assertSalidaDelPanel($this->get('/admin/profile'), ProtegerSesionDelPanel::AVISO_INACTIVIDAD);
});

it('guards every staff download under /panel with the panel session', function (): void {
    $descargas = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($ruta): bool => str_starts_with((string) $ruta->getName(), 'panel.'));

    expect($descargas)->not->toBeEmpty()
        ->and($descargas->reject(fn ($ruta): bool => in_array('panel.sesion', $ruta->gatherMiddleware(), true))->map->getName()->values()->all())->toBe([]);
});
