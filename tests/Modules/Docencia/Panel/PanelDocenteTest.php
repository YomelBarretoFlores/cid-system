<?php

declare(strict_types=1);

use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\PortalDocente;

it('sends a guest to the portal sign-in', function (string $ruta): void {
    $this->get($ruta)->assertRedirect(route('ingresar'));
})->with(['/docente', '/docente/actas']);

it('refuses an account without a teacher profile, staff included', function (User $usuario): void {
    $this->actingAs($usuario)
        ->get('/docente')
        ->assertForbidden()
        ->assertSee('Tu cuenta no está vinculada a un perfil docente del CILN.');
})->with([
    'a student' => fn (): User => tap(User::factory()->create(), fn (User $usuario) => Persona::factory()->create(['user_id' => $usuario->id])),
    'staff' => fn (): User => User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION),
]);

it('lets a teacher whose email was never verified into the panel', function (): void {
    $usuario = User::factory()->unverified()->create();
    Docente::factory()->create(['persona_id' => Persona::factory()->create(['user_id' => $usuario->id])->id]);

    $this->actingAs($usuario)->get('/docente')->assertOk();
});

it('keeps a teacher who is not staff out of the admin panel', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)->get('/admin')->assertForbidden();
});

it('opens the teacher panel without the staff sign-in or a second factor', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)
        ->get('/docente')
        ->assertOk()
        ->assertSee('PORTAL DOCENTE');
});

it('links every teacher to the student portal from the user menu', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)
        ->get('/docente')
        ->assertSee('Mi portal de estudiante')
        ->assertSee(route('estudiante.inicio'));
});

it('signs the teacher out to the portal sign-in', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)
        ->post(route('filament.docente.auth.logout'))
        ->assertRedirect(route('ingresar'));

    $this->assertGuest();
});

it('still signs staff out to the panel sign-in', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION))
        ->post(route('filament.admin.auth.logout'))
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('answers not found for a group segment that is not a number', function (string $pagina): void {
    [$usuario] = PortalDocente::docente();

    $this->actingAs($usuario)->get("/docente/grupos/abc/{$pagina}")->assertNotFound();
})->with(['asistencia', 'notas', 'acta']);
