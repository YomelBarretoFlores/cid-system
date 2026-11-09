<?php

declare(strict_types=1);

use App\Modules\Matricula\Livewire\MisReportes;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Livewire\ModificarDatos;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

// Authorization sweep of the student portal as one attack surface: the guards must hold even when a resource id,
// a query parameter or a Livewire property is forged. Each module keeps its own cross-student test; this pins the contract whole.

it('sends a signed-out visitor to the sign-in on every student page', function (string $ruta): void {
    $this->get(route($ruta))->assertRedirect(route('ingresar'));
})->with([
    'estudiante.inicio', 'estudiante.matricula', 'estudiante.matriculas', 'estudiante.reportes',
    'estudiante.pagos', 'estudiante.tramites', 'estudiante.clasificacion', 'estudiante.clases',
    'estudiante.perfil.datos', 'estudiante.perfil.foto', 'estudiante.perfil.foto.ver', 'estudiante.preferencias',
]);

it('keeps the portal behind a verified email', function (): void {
    $user = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))->assertRedirect(route('verification.notice'));
});

it('refuses the portal to an authenticated account without a student record', function (): void {
    $this->actingAs(User::factory()->create())->get(route('estudiante.inicio'))->assertForbidden();
});

it('refuses to set a protected field on the edit-data form and leaves the person untouched', function (string $campo): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id, 'nombres' => 'Rosa', 'numero_documento' => '41234567', 'correo' => 'rosa@example.test']);

    expect(fn () => Livewire::actingAs($user)->test(ModificarDatos::class)->set($campo, 'forzado'))
        ->toThrow(PublicPropertyNotFoundException::class);

    expect($persona->fresh()->only(['nombres', 'numero_documento', 'correo']))
        ->toBe(['nombres' => 'Rosa', 'numero_documento' => '41234567', 'correo' => 'rosa@example.test']);
})->with(['nombres', 'apellido_paterno', 'correo', 'email', 'numero_documento', 'fecha_nacimiento', 'tipo_vinculo', 'personaId', 'persona_id']);

it('answers a forged period in the address with the newest report, not a server error', function (string $periodo): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.reportes', ['periodo' => $periodo]))->assertOk();
})->with(['abc', '99999999', '-1', '0', '1 OR 1=1']);

it('cannot be driven to a period the student does not own through the selector', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test(MisReportes::class)
        ->set('periodo', 99999999)
        ->assertSet('periodo', null);
});

it('refuses a portal-only account every staff download and never signs it out', function (string $ruta): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    $parametro = $ruta === 'panel.personas.foto' ? $persona : Matricula::factory()->activa()->create(['persona_id' => $persona->id]);

    // be() signs in without the panel-session mark that actingAs() adds, as a real portal session would.
    $this->be($user)->get(route($ruta, $parametro))->assertForbidden();

    $this->be($user)->get(route('estudiante.inicio'))->assertOk();
})->with(['panel.matriculas.ficha', 'panel.personas.foto']);
