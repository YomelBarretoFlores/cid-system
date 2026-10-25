<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PortalDocente;

it('shows each entry with its own heading and a link to the other one', function (): void {
    $this->get(route('docente.ingresar'))
        ->assertOk()
        ->assertSee('Ingresar al portal docente')
        ->assertSee(route('estudiante.ingresar'), false)
        ->assertDontSee(route('estudiante.registro'), false);

    $this->get(route('estudiante.ingresar'))
        ->assertOk()
        ->assertSee('Ingresar al portal del estudiante')
        ->assertSee(route('docente.ingresar'), false);
});

it('lands a teacher on the teacher module from the teacher entry and on the student module from the student entry', function (string $entrada, string $destino): void {
    [$usuario] = PortalDocente::docente();

    Livewire::test(IniciarSesion::class, ['portal' => $entrada])
        ->set('email', $usuario->email)
        ->set('password', 'password')
        ->call('ingresar')
        ->assertHasNoErrors()
        ->assertRedirect(route($destino));

    $this->assertAuthenticatedAs($usuario);
})->with([
    'teacher entry' => ['docente', 'docente.grupos'],
    'student entry' => ['estudiante', 'estudiante.inicio'],
]);

it('lands a student without a teacher profile on the student module even from the teacher entry', function (): void {
    $usuario = User::factory()->create();
    Persona::factory()->create(['user_id' => $usuario->id]);

    Livewire::test(IniciarSesion::class, ['portal' => 'docente'])
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('estudiante.inicio'));
});

it('lands staff without a person record on the panel', function (): void {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION, 'web'));

    Livewire::test(IniciarSesion::class)
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('sends guests of teacher pages to the teacher entry and back to the requested page after signing in', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->get(route('docente.actas'))->assertRedirect(route('docente.ingresar'));

    Livewire::test(IniciarSesion::class, ['portal' => 'docente'])
        ->set('email', $usuario->email)->set('password', 'password')->call('ingresar')
        ->assertRedirect(route('docente.actas'));
});

it('sends a signed-in teacher away from the teacher entry to the teacher module', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)->get(route('docente.ingresar'))->assertRedirect(route('docente.grupos'));
    $this->actingAs($usuario)->get(route('estudiante.ingresar'))->assertRedirect(route('estudiante.inicio'));
});

it('ignores an unknown entry and treats it as the student entry', function (): void {
    Livewire::test(IniciarSesion::class, ['portal' => 'admin'])->assertSee('Ingresar al portal del estudiante');
});

it('keeps sending signed-in staff from the entry pages to the panel, even with a person record', function (): void {
    $usuario = User::factory()->create();
    Persona::factory()->create(['user_id' => $usuario->id]);
    $usuario->givePermissionTo(Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION, 'web'));

    $this->actingAs($usuario)->get(route('estudiante.ingresar'))->assertRedirect(route('filament.admin.pages.dashboard'));
});
