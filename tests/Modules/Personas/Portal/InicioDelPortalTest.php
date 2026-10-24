<?php

declare(strict_types=1);

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

it('greets the student by name on the portal home', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id, 'nombres' => 'Rosa Elena', 'apellido_paterno' => 'Quispe', 'numero_documento' => '41234567']);

    $this->actingAs($user)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Hola, Rosa Elena')
        ->assertSee('DNI 41234567');
});

it('refuses the portal to an account without a student record', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('estudiante.inicio'))
        ->assertForbidden()
        ->assertSee('Tu cuenta no está vinculada a una ficha de estudiante del CILN.');
});

it('describes the student record of a user and nothing for other users', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id, 'nombres' => 'Rosa', 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'numero_documento' => '41234567']);

    expect(app(FichaDelUsuario::class)($user->id))->toEqual(new FichaDeEstudiante(
        personaId: $persona->id,
        nombres: 'Rosa',
        nombreCompleto: 'Quispe Mamani, Rosa',
        documento: 'DNI 41234567',
    ))->and(app(FichaDelUsuario::class)(User::factory()->create()->id))->toBeNull();
});
