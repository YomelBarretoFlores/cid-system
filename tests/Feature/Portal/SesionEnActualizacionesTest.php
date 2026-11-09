<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Testing\TestResponse;

// A page left open on another device must stop working once the password changes, not only on its next full load.
function actualizarComponente(object $prueba, string $html): TestResponse
{
    preg_match('/wire:snapshot="([^"]+)"/', $html, $coincidencia);
    $snapshot = html_entity_decode($coincidencia[1] ?? '', ENT_QUOTES);

    return $prueba->withHeaders(['X-Livewire' => 'true'])->postJson(route('default-livewire.update'), [
        'components' => [['snapshot' => $snapshot, 'updates' => new stdClass, 'calls' => []]],
    ]);
}

beforeEach(function (): void {
    $this->alumno = User::factory()->create(['password' => 'clave-actual-1']);
    Persona::factory()->create(['user_id' => $this->alumno->id]);
});

it('keeps a Livewire update working while the password is unchanged', function (): void {
    $html = $this->be($this->alumno)->get(route('estudiante.perfil.datos'))->assertOk()->getContent();

    actualizarComponente($this, (string) $html)->assertOk();
    $this->assertAuthenticatedAs($this->alumno);
});

it('signs out a Livewire update from a session opened before the password changed elsewhere', function (): void {
    $html = $this->be($this->alumno)->get(route('estudiante.perfil.datos'))->assertOk()->getContent();

    $this->alumno->forceFill(['password' => 'clave-nueva-2'])->save();

    expect(actualizarComponente($this, (string) $html)->status())->not->toBe(200);
    $this->assertGuest();
});
