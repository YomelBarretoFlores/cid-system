<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PortalDocente;

it('shows a single entry for students and teachers with the link to create an account', function (): void {
    $this->get('/ingresar')
        ->assertOk()
        ->assertSee('Ingresar al portal del CILN')
        ->assertSee(route('estudiante.registro'), false)
        ->assertDontSee('/docente/ingresar', false)
        ->assertDontSee('/estudiante/ingresar', false);
});

it('sends the former student and teacher entries to the single entry', function (string $anterior): void {
    $this->get($anterior)->assertStatus(301)->assertRedirect('/ingresar');
})->with(['/estudiante/ingresar', '/docente/ingresar']);

it('lands each account on the module of its profile', function (): void {
    [$docente] = PortalDocente::docente();
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    foreach ([[$docente, 'docente.grupos'], [$estudiante, 'estudiante.inicio']] as [$usuario, $destino]) {
        Livewire::test(IniciarSesion::class)
            ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
            ->assertHasNoErrors()
            ->assertRedirect(route($destino));

        $this->assertAuthenticatedAs($usuario);
        auth()->logout();
    }
});

it('lands staff without a person record on the panel', function (): void {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION, 'web'));

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('sends guests of portal pages to the single entry and back to the requested page after signing in', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->get(route('docente.actas'))->assertRedirect(route('ingresar'));
    $this->get(route('estudiante.pagos'))->assertRedirect(route('ingresar'));

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('estudiante.pagos'));
});

it('sends a signed-in account away from the entry to the module of its profile', function (): void {
    [$docente] = PortalDocente::docente();
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    $this->actingAs($docente)->get('/ingresar')->assertRedirect(route('docente.grupos'));
    $this->actingAs($estudiante)->get('/ingresar')->assertRedirect(route('estudiante.inicio'));
});

it('keeps sending signed-in staff from the entry to the panel, even with a person record', function (): void {
    $usuario = User::factory()->create();
    Persona::factory()->create(['user_id' => $usuario->id]);
    $usuario->givePermissionTo(Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION, 'web'));

    $this->actingAs($usuario)->get('/ingresar')->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('ignores a pending panel page when signing in through the portal', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->get('/admin')->assertRedirect('/admin/login');

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('docente.grupos'));
});
