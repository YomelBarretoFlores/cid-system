<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Testing\TestResponse;

function sondaSnapshot(TestResponse $pagina): string
{
    preg_match('/wire:snapshot="([^"]+)"/', $pagina->getContent(), $coincidencia);

    return html_entity_decode($coincidencia[1], ENT_QUOTES);
}

/**
 * @param  array<string, mixed>  $updates
 * @param  list<array<string, mixed>>  $calls
 */
function sondaActualizar(string $snapshot, array $updates = [], array $calls = []): TestResponse
{
    return test()->withHeaders(['X-Livewire' => '1'])
        ->postJson(route('default-livewire.update'), ['components' => [['snapshot' => $snapshot, 'updates' => $updates, 'calls' => $calls]]]);
}

it('does not let an unverified account read its payments by replaying a portal snapshot', function (): void {
    $verificado = User::factory()->create();
    Persona::factory()->create(['user_id' => $verificado->id]);
    $snapshot = sondaSnapshot($this->actingAs($verificado)->get(route('estudiante.pagos'))->assertOk());

    $sinVerificar = User::factory()->unverified()->create();
    $victima = Persona::factory()->create(['user_id' => $sinVerificar->id]);
    Pago::factory()->rechazado()->create(['persona_id' => $victima->id, 'numero_operacion' => 'OP-VICTIMA-7']);

    $this->actingAs($sinVerificar)->get(route('estudiante.pagos'))->assertRedirect(route('verification.notice'));

    $respuesta = sondaActualizar($snapshot);

    expect($respuesta->getContent())->not->toContain('OP-VICTIMA-7');
});

it('does not keep serving portal data to a session ended by a password change', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    Pago::factory()->create(['persona_id' => $persona->id, 'numero_operacion' => 'OP-PROPIA-3']);
    $snapshot = sondaSnapshot($this->actingAs($user)->get(route('estudiante.pagos'))->assertOk());

    $user->forceFill(['password' => 'otra-clave99'])->save();

    expect(sondaActualizar($snapshot)->getContent())->not->toContain('OP-PROPIA-3');
});

it('does not let an unverified account enroll by replaying a portal snapshot', function (): void {
    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->for(Ciclo::factory()->state(['numero' => 1]))->create();
    $verificado = User::factory()->create();
    Persona::factory()->create(['user_id' => $verificado->id]);
    $snapshot = sondaSnapshot($this->actingAs($verificado)->get(route('estudiante.matricula'))->assertOk());

    $sinVerificar = User::factory()->unverified()->create();
    $victima = Persona::factory()->create(['user_id' => $sinVerificar->id]);
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $victima->id]);

    $this->actingAs($sinVerificar);
    sondaActualizar($snapshot, ['grupo' => (string) $grupo->id, 'pago' => (string) $pago->id], [['method' => 'matricularme', 'params' => [], 'metadata' => []]]);

    expect(Matricula::query()->where('persona_id', $victima->id)->exists())->toBeFalse();
});
