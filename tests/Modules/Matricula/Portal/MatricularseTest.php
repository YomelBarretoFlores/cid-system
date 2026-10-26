<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->idioma = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->ciclo1 = Ciclo::factory()->for($this->idioma)->create(['numero' => 1]);
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $this->grupo = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create(['codigo' => 'ING-I-A', 'cupo_maximo' => 20]);
    $this->concepto = Concepto::factory()->create(['nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula]);
    $this->pago = Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-111']);
});

it('shows the open groups of the enabled cycle with schedule and live vacancies', function (): void {
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    Grupo::factory()->for($this->periodo)->for(Ciclo::factory()->for($this->idioma)->state(['numero' => 2]))->create(['codigo' => 'ING-II-A']);

    $this->actingAs($this->user)
        ->get(route('estudiante.matricula'))
        ->assertOk()
        ->assertSee('Inglés')
        ->assertSee('ING-I-A')
        ->assertSee('Lun, 08:00–10:00')
        ->assertSee('20 vacantes')
        ->assertSee('wire:poll.15s', false)
        ->assertDontSee('ING-II-A');
});

it('enrolls the student with an unused validated payment and opens my enrollments', function (): void {
    Livewire::actingAs($this->user)
        ->test(Matricularse::class)
        ->set('grupo', (string) $this->grupo->id)
        ->set('pago', (string) $this->pago->id)
        ->call('matricularme')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.matriculas'));

    expect(Matricula::query()->sole())
        ->persona_id->toBe($this->persona->id)
        ->grupo_id->toBe($this->grupo->id)
        ->pago_id->toBe($this->pago->id)
        ->and($this->grupo->refresh()->matriculados)->toBe(1)
        ->and(session('estado'))->toBe('Te matriculaste en el grupo ING-I-A.');
});

it('shows the business refusal and enrolls nothing', function (): void {
    $componente = Livewire::actingAs($this->user)
        ->test(Matricularse::class)
        ->set('grupo', (string) $this->grupo->id)
        ->set('pago', (string) $this->pago->id);
    $this->grupo->forceFill(['matriculados' => 20])->save();

    $componente->call('matricularme')
        ->assertHasErrors(['matricula' => 'El grupo no tiene vacantes disponibles.'])
        ->assertSee('El grupo no tiene vacantes disponibles.')
        ->assertNoRedirect();

    expect(Matricula::query()->exists())->toBeFalse()
        ->and($this->grupo->refresh()->matriculados)->toBe(20);
});

it('offers only payments not used by an enrollment', function (): void {
    Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-222']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'pago_id' => $this->pago->id]);

    Livewire::actingAs($this->user)
        ->test(Matricularse::class)
        ->assertSee('OP-222')
        ->assertDontSee('OP-111')
        ->set('grupo', (string) $this->grupo->id)
        ->set('pago', (string) $this->pago->id)
        ->call('matricularme')
        ->assertHasErrors(['pago']);
});

it('refuses a payment of another person without enrolling', function (): void {
    $ajeno = Pago::factory()->validado()->for($this->concepto)->create();

    Livewire::actingAs($this->user)
        ->test(Matricularse::class)
        ->set('grupo', (string) $this->grupo->id)
        ->set('pago', (string) $ajeno->id)
        ->call('matricularme')
        ->assertHasErrors(['pago']);

    expect(Matricula::query()->exists())->toBeFalse();
});

it('asks a student without a validated enrollment payment to register one', function (): void {
    $sinPago = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $sinPago->id]);
    Pago::factory()->pendiente()->for($this->concepto)->create(['persona_id' => $persona->id]);

    Livewire::actingAs($sinPago)
        ->test(Matricularse::class)
        ->assertSee('Aún no tienes un pago de matrícula validado.')
        ->assertSee(route('estudiante.pagos'))
        ->assertDontSee('Matricularme');
});

it('sends guests of the enrollment pages to the portal sign-in', function (string $ruta): void {
    $this->get(route($ruta))->assertRedirect(route('ingresar'));
})->with(['estudiante.matricula', 'estudiante.matriculas']);

it('refuses the enrollment pages to an account without a student record', function (string $ruta): void {
    $this->actingAs(User::factory()->create())
        ->get(route($ruta))
        ->assertForbidden();
})->with(['estudiante.matricula', 'estudiante.matriculas']);
