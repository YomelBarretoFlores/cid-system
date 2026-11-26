<?php

declare(strict_types=1);

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    $this->constancia = TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->sole();
});

it('lists the student\'s trámites in a table with their code, stage, state and observation, and badges the observed ones in the menu', function (): void {
    $observado = Tramite::factory()->for($this->constancia)->create(['persona_id' => $this->persona->id, 'etapa_actual' => 1]);
    DB::table('tra_tramites')->where('id', $observado->id)->update(['estado' => 'observado', 'observacion' => 'El recibo no se lee. Sube una foto más nítida.']);
    $ajeno = Tramite::factory()->for($this->constancia)->create();

    $this->actingAs($this->user)->get(route('estudiante.tramites'))
        ->assertOk()
        ->assertSeeTextInOrder(['Mis trámites', 'Código', 'Trámite', 'Iniciado el', 'Etapa actual', 'Estado', 'Documento',
            $observado->refresh()->codigo, 'Constancia de matrícula', $observado->created_at->timezone('America/Lima')->format('d/m/Y'), 'Pago validado', 'Observado', '—',
            'El recibo no se lee. Sube una foto más nítida.', 'Ver etapas', 'Nuevo trámite'])
        ->assertSeeText('Etapa 2 de 4: Pago validado')
        ->assertSeeText('33 % de avance')
        ->assertSeeTextInOrder(['Trámites', '1'])
        ->assertDontSeeText($ajeno->refresh()->codigo);
});

it('starts a trámite chosen from the list of types with the data its final action asks for', function (): void {
    $this->actingAs($this->user);

    Livewire::test(MisTramites::class)
        ->assertSeeTextInOrder(['Nuevo trámite', 'Trámite', 'Tasa', 'Constancia de matrícula', 'Constancia', 'Elegir'])
        ->assertSeeHtml('wire:click="elegir('.$this->constancia->id.')"')
        ->assertDontSeeText('Elige tu matrícula')
        ->call('elegir', $this->constancia->id)
        ->assertSeeText('Constancia de matrícula')
        ->assertSeeText('Requisitos: Una matrícula activa y el pago de la tasa.')
        ->assertSeeText('Elige tu matrícula')
        ->assertSeeText('Este trámite tiene una tasa (Constancia).')
        ->set('matricula', (string) $this->matricula->id)
        ->call('solicitar')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.tramites'));

    $tramite = Tramite::query()->where('persona_id', $this->persona->id)->sole();
    expect($tramite)->estado->toBe(EstadoTramite::PendientePago)
        ->and(session('estado'))->toContain("Registramos tu trámite {$tramite->codigo}. Paga la tasa");
});

it('requests a level certificate from the levels the student completed', function (): void {
    $this->actingAs($this->user);
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'CERTIFICADO-NIVEL')->value('id'))
        ->assertSeeText('Inglés · Básico')
        ->set('nivel', 'Inglés|basico')
        ->call('solicitar')
        ->assertHasNoErrors();

    expect(Tramite::query()->where('persona_id', $this->persona->id)->sole()->datos)->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'basico']);
});

it('requests a grade record choosing among the levels with a passed cycle', function (): void {
    $this->actingAs($this->user);
    ActasFinales::historial($this->persona, 'Italiano', [[1, '15.00', 'aprobado'], [5, '08.00', 'desaprobado']]);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->value('id'))
        ->assertSeeText('Idioma y nivel')
        ->assertSeeText('Italiano · Básico')
        ->assertDontSeeText('Italiano · Intermedio')
        ->set('nivel', 'Italiano|basico')
        ->call('solicitar')
        ->assertHasNoErrors();

    expect(Tramite::query()->where('persona_id', $this->persona->id)->sole()->datos)->toMatchArray(['idioma' => 'Italiano', 'nivel' => 'basico']);
});

it('shows why a request is refused and keeps the form open', function (): void {
    $this->actingAs($this->user);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'RETIRO')->value('id'))
        ->set('matricula', (string) $this->matricula->id)
        ->set('motivo', 'no')
        ->call('solicitar')
        ->assertHasErrors('solicitud')
        ->assertSeeText('Indica el motivo, entre 5 y 500 caracteres.')
        ->assertSeeText('Retiro del ciclo');

    expect(Tramite::query()->count())->toBe(0);
});

it('ignores a type that is not available and does not let the browser choose it', function (): void {
    $this->actingAs($this->user);
    $inactivo = TipoTramite::factory()->sinTasa()->create(['activo' => false]);

    Livewire::test(MisTramites::class)->call('elegir', $inactivo->id)->assertSet('tipoElegido', null)->call('solicitar');

    expect(Tramite::query()->count())->toBe(0)
        ->and(fn () => Livewire::test(MisTramites::class)->set('tipoElegido', $this->constancia->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('sends guests to the portal sign-in', function (): void {
    $this->get(route('estudiante.tramites'))->assertRedirect(route('ingresar'));
});
