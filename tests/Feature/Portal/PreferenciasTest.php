<?php

declare(strict_types=1);

use App\Modules\Personas\Livewire\Preferencias;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Enums\TemaDeInterfaz;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

function estudianteConTema(TemaDeInterfaz $tema = TemaDeInterfaz::Sistema): User
{
    $user = User::factory()->create(['name' => 'Rosa Elena Quispe', 'tema' => $tema]);
    Persona::factory()->create(['user_id' => $user->id]);

    return $user;
}

it('gives every account the system theme by default and refuses a theme the portal does not offer', function (): void {
    $id = DB::table('users')->insertGetId(['name' => 'Cuenta sintética', 'email' => 'sintetica@example.test', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]);

    expect(DB::table('users')->where('id', $id)->value('tema'))->toBe('sistema')
        ->and(User::query()->findOrFail($id)->tema)->toBe(TemaDeInterfaz::Sistema)
        ->and(fn () => DB::table('users')->where('id', $id)->update(['tema' => 'azul']))
        ->toThrow(QueryException::class, 'users_tema_valido_check');
});

it('lets the student pick light, dark or system theme in Preferencias', function (): void {
    $this->actingAs(estudianteConTema(TemaDeInterfaz::Oscuro))->get(route('estudiante.preferencias'))
        ->assertOk()
        ->assertSeeInOrder(['Preferencias', 'Configura cómo se ve tu portal del CILN.', 'Modo oscuro', 'Claro', 'Oscuro', 'Del sistema', 'Guardar cambios'])
        ->assertSee('value="oscuro"', false);

    Livewire::actingAs(estudianteConTema(TemaDeInterfaz::Oscuro))->test(Preferencias::class)->assertSet('tema', 'oscuro');
});

it('saves the chosen theme on the signed-in account only, confirms it and reloads the page so it applies', function (TemaDeInterfaz $elegido, TemaDeInterfaz $anterior): void {
    $alumno = estudianteConTema($anterior);
    $otro = estudianteConTema($anterior);

    Livewire::actingAs($alumno)->test(Preferencias::class)
        ->set('tema', $elegido->value)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.preferencias'));

    expect($alumno->fresh()->tema)->toBe($elegido)
        ->and($otro->fresh()->tema)->toBe($anterior)
        ->and(session('estado'))->toBe('Guardamos tus preferencias.');
})->with([
    'claro' => [TemaDeInterfaz::Claro, TemaDeInterfaz::Sistema],
    'oscuro' => [TemaDeInterfaz::Oscuro, TemaDeInterfaz::Claro],
    'del sistema' => [TemaDeInterfaz::Sistema, TemaDeInterfaz::Oscuro],
]);

it('refuses a theme outside the three options and keeps the saved one', function (): void {
    $alumno = estudianteConTema(TemaDeInterfaz::Claro);

    Livewire::actingAs($alumno)->test(Preferencias::class)
        ->set('tema', 'azul')
        ->call('guardar')
        ->assertHasErrors('tema')
        ->assertNoRedirect();

    expect($alumno->fresh()->tema)->toBe(TemaDeInterfaz::Claro);
});

it('cannot be pointed at another account through a forged property', function (string $campo): void {
    $alumno = estudianteConTema();

    expect(fn () => Livewire::actingAs($alumno)->test(Preferencias::class)->set($campo, 1))
        ->toThrow(PublicPropertyNotFoundException::class);
})->with(['usuarioId', 'userId', 'user_id']);

it('sends a signed-out visitor to the sign-in', function (): void {
    $this->get(route('estudiante.preferencias'))->assertRedirect(route('ingresar'));
});

it('paints the portal dark from the server for the dark theme, so it does not flash', function (): void {
    $this->actingAs(estudianteConTema(TemaDeInterfaz::Oscuro))->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('<html lang="es" class="oscuro">', false)
        ->assertDontSee('data-tema-del-sistema', false);
});

it('leaves the portal light for the light theme', function (): void {
    $this->actingAs(estudianteConTema(TemaDeInterfaz::Claro))->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('<html lang="es">', false)
        ->assertDontSee('class="oscuro"', false)
        ->assertDontSee('data-tema-del-sistema', false);
});

it('follows the device theme before the styles load for the system theme', function (): void {
    $this->actingAs(estudianteConTema(TemaDeInterfaz::Sistema))->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('<html lang="es">', false)
        ->assertSeeInOrder(['<head>', 'data-tema-del-sistema', "matchMedia('(prefers-color-scheme: dark)')", '<title>'], false);
});

it('never applies a theme to the public site, even for a student who chose the dark one', function (): void {
    $this->actingAs(estudianteConTema(TemaDeInterfaz::Oscuro))->get(route('portada'))
        ->assertOk()
        ->assertSee('<html lang="es">', false)
        ->assertDontSee('class="oscuro"', false)
        ->assertDontSee('data-tema-del-sistema', false);
});

it('never applies a theme to the sign-in page', function (): void {
    $this->get(route('ingresar'))
        ->assertOk()
        ->assertSee('<html lang="es">', false)
        ->assertDontSee('class="oscuro"', false)
        ->assertDontSee('data-tema-del-sistema', false);
});
