<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

it('shows the student profile on top of the sidebar and groups the sections like the SGA', function (): void {
    $user = User::factory()->create(['name' => 'Rosa Elena Quispe']);
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder([
            'Rosa Elena Quispe', 'Estudiante',
            'Mi cuenta', 'Perfil', 'Modificar datos', 'Subir fotografía', 'Preferencias',
            'Reportes', 'Todos', 'Horario', 'Sílabos',
            'Procesos', 'Matrícula', 'Mis matrículas', 'Registro de pagos', 'Clasificación', 'Trámites',
            'Enlaces', 'Verificar un documento', 'Web del CILN',
            'Cerrar sesión',
        ])
        ->assertSee('href="'.route('verificacion.documento').'"', false)
        ->assertSee('href="'.route('portada').'"', false)
        ->assertSee('href="'.route('estudiante.reportes', ['ver' => 'silabos']).'"', false)
        ->assertSee('href="'.route('estudiante.perfil.datos').'"', false)
        ->assertSee('href="'.route('estudiante.perfil.foto').'"', false)
        ->assertSee('href="'.route('estudiante.preferencias').'"', false);
});

it('draws the sidebar collapsed from the server when the student left it collapsed, so it does not blink on navigation', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))->assertDontSee('data-colapsado="true"', false);

    $this->withUnencryptedCookie('ciln-menu-colapsado', '1')
        ->get(route('estudiante.inicio'))
        ->assertSee('data-colapsado="true"', false);
});

it('keeps the account menu and the collapse control at the foot of the sidebar, like the staff panels', function (): void {
    $user = User::factory()->create(['name' => 'Rosa Elena Quispe']);
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('antialiased tema-portal"', false)
        ->assertSeeInOrder(['Web del CILN', 'id="menu-de-cuenta"', 'Mi perfil', 'Preferencias', 'Cerrar sesión', 'Contraer el menú', 'Ctrl B'], false);
});

it('heads every process page with the SGA trail: Alumno, the student and the page', function (): void {
    $user = User::factory()->create(['name' => 'Rosa Elena Quispe']);
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.pagos'))
        ->assertOk()
        ->assertSeeInOrder(['Ruta de navegación', 'Alumno', 'ROSA ELENA QUISPE', 'Registro de pagos'])
        ->assertSee('href="'.route('estudiante.inicio').'"', false);
});

it('keeps the back control of a page that names where to go back', function (): void {
    $user = User::factory()->create(['name' => 'Rosa Elena Quispe']);
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.perfil.foto'))
        ->assertOk()
        ->assertSeeInOrder(['Ruta de navegación', 'href="'.route('estudiante.inicio').'" wire:navigate data-volver', 'Volver', 'Fotografía personal'], false);

    $this->get(route('estudiante.pagos'))->assertOk()->assertDontSee('data-volver', false);
});
