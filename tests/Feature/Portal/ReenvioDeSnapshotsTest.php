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

it('does not let an account not yet enabled read another student payments by replaying a portal snapshot', function (): void {
    $ajeno = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $ajeno->id]);
    Pago::factory()->rechazado()->create(['persona_id' => $persona->id, 'numero_operacion' => 'OP-AJENO-7']);
    $snapshot = sondaSnapshot($this->actingAs($ajeno)->get(route('estudiante.pagos'))->assertOk());

    $sinHabilitar = User::factory()->create();
    Persona::factory()->create(['user_id' => $sinHabilitar->id, 'habilitada_en' => null]);
    $this->actingAs($sinHabilitar);

    expect(sondaActualizar($snapshot)->getContent())->not->toContain('OP-AJENO-7');
});

it('does not keep serving portal data to a session ended by a password change', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    Pago::factory()->create(['persona_id' => $persona->id, 'numero_operacion' => 'OP-PROPIA-3']);
    $snapshot = sondaSnapshot($this->actingAs($user)->get(route('estudiante.pagos'))->assertOk());

    $user->forceFill(['password' => 'otra-clave99'])->save();

    expect(sondaActualizar($snapshot)->getContent())->not->toContain('OP-PROPIA-3');
});

it('does not let an account not yet enabled enroll by replaying a portal snapshot', function (): void {
    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->for(Ciclo::factory()->state(['numero' => 1]))->create();
    $verificado = User::factory()->create();
    Persona::factory()->create(['user_id' => $verificado->id]);
    $snapshot = sondaSnapshot($this->actingAs($verificado)->get(route('estudiante.matricula'))->assertOk());

    $sinVerificar = User::factory()->create();
    $victima = Persona::factory()->create(['user_id' => $sinVerificar->id, 'habilitada_en' => null]);
    $pago = Pago::factory()->validado()->for(Concepto::factory()->state(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $victima->id]);

    $this->actingAs($sinVerificar);
    sondaActualizar($snapshot, ['grupo' => (string) $grupo->id, 'pago' => (string) $pago->id], [['method' => 'matricularme', 'params' => [], 'metadata' => []]]);

    expect(Matricula::query()->where('persona_id', $victima->id)->exists())->toBeFalse();
});
