<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Docencia\Data\EtapasDelActa;
use App\Modules\Docencia\Filament\Docente\Pages\PreActaDelGrupo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    Filament::setCurrentPanel('docente');
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $this->travelTo(CarbonImmutable::parse('2027-03-15 10:00', 'America/Lima'));
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'periodo_id' => Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'actas_inicio' => '2027-03-28', 'actas_fin' => '2027-04-05'])->id, 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($this->grupo, $this->docente);
    $esquema = EsquemaEvaluacion::factory()->create(['nombre' => 'Esquema general', 'nota_maxima' => '20.00', 'vigente_desde' => '2027-01-01']);
    $this->oral = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'nombre' => 'Expresión oral', 'orden' => 1]);
    $this->parcial = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral', 'parcial'], redondeo: 'entero', ciclo: 1)->create(['codigo' => 'nota_final', 'nombre' => 'Nota final calculada', 'orden' => 3]);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id]);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez', 'apellido_materno' => 'Mejía', 'nombres' => 'Luis'])->id]);
});

it('links the pre-acta from the list of groups', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertSee(route('filament.docente.pages.grupos.notas', $this->grupo->id));
});

it('shows the columns the teacher enters, not the calculated ones, and the students in alphabetical order', function (): void {
    NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'componente_id' => $this->parcial->id, 'nota' => '14.50', 'registrado_por' => $this->usuario->id]);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Pre-acta del grupo ING-01-A', 'Esquema general · notas de 0 a 20', 'Expresión oral', 'Examen parcial', 'Expresión oral', 'Examen parcial', 'Álvarez Mejía, Luis', 'Quispe Rojas, Ana'])
        ->assertDontSee(['Peso', 'Nota final calculada']);

    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet("notas.{$this->ana->id}.{$this->parcial->id}", '14.50')
        ->assertSet("notas.{$this->ana->id}.{$this->oral->id}", '');
});

it('saves the grades typed by the teacher', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->ana->id}.{$this->parcial->id}", '15')
        ->set("notas.{$this->luis->id}.{$this->oral->id}", '12,5')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Notas parciales guardadas.')
        ->assertSet("notas.{$this->luis->id}.{$this->oral->id}", '12.50');

    expect(NotaParcial::query()->orderBy('nota')->pluck('nota')->all())->toBe(['12.50', '15.00']);
});

it('shows and logs a rejected grade and saves nothing', function (): void {
    Log::spy();

    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->ana->id}.{$this->parcial->id}", '15')
        ->set("notas.{$this->luis->id}.{$this->parcial->id}", '25')
        ->call('guardar')
        ->assertHasErrors('notas')
        ->assertSee('La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.');

    expect(NotaParcial::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.'])->once();
});

it('tells the teacher when the group has no evaluation scheme', function (): void {
    EsquemaEvaluacion::query()->update(['vigente_desde' => '2027-03-02']);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSee('El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.')
        ->assertDontSee('Guardar notas');
});

it('hides the pre-acta of a group the teacher does not have now', function (): void {
    $pagina = Livewire::actingAs($this->usuario)->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->call('guardar')->assertNotFound();
    Livewire::actingAs($this->usuario)->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])->assertNotFound();

    expect(NotaParcial::query()->count())->toBe(0);
});

it('shows how many grades each component has and that the acta waits for the missing ones', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    Periodo::query()->whereKey($this->grupo->periodo_id)->update(['actas_inicio' => '2027-03-28', 'actas_fin' => '2027-03-31']);
    NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'componente_id' => $this->parcial->id, 'nota' => '14.50', 'registrado_por' => $this->usuario->id]);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Registra las notas', '1 de 4 notas', 'Revisa el acta', 'Cuando completes las notas', 'Valida y cierra', 'Del 28 mar al 31 mar',
            'Expresión oral', '0 de 2', 'Examen parcial', '1 de 2',
        ]);
});

it('points the teacher to review and close the acta once every grade is in and the window is open', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-29 10:00', 'America/Lima'));
    Periodo::query()->whereKey($this->grupo->periodo_id)->update(['actas_inicio' => '2027-03-28', 'actas_fin' => '2027-03-31']);
    foreach ([$this->ana, $this->luis] as $matricula) {
        foreach ([$this->parcial, $this->oral] as $componente) {
            NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $matricula->id, 'componente_id' => $componente->id, 'nota' => '15.00', 'registrado_por' => $this->usuario->id]);
        }
    }

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Registra las notas', '4 de 4 notas', 'Revisa el acta', 'Revisa los promedios antes de validar', 'Valida y cierra', 'Hasta el 31 mar'])
        ->assertSee(route('filament.docente.pages.grupos.acta', $this->grupo->id));
});

it('marks every stage done once the acta is closed', function (): void {
    $etapas = EtapasDelActa::de(4, 4, EstadoActa::Cerrada, CarbonImmutable::parse('2027-03-28'), CarbonImmutable::parse('2027-03-31'), CarbonImmutable::parse('2027-04-02'));

    expect(array_map(fn (array $etapa): array => [$etapa[2], $etapa[3]], $etapas))->toBe([['4 de 4 notas', 'hecho'], ['Revisada', 'hecho'], ['Cerrada', 'hecho']])
        ->and(EtapasDelActa::de(0, 4, null, null, null, CarbonImmutable::parse('2027-04-02'))[2][2])->toBe('Sin fechas de actas');
});

it('shows the grades read-only with the calendar outside the dates for partial grades', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-06 08:00', 'America/Lima'));
    NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'componente_id' => $this->parcial->id, 'nota' => '14.50', 'registrado_por' => $this->usuario->id]);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Las notas parciales del grupo ING-01-A se registran del 01/03/2027 al 05/04/2027.', 'Quispe Rojas, Ana', '14.50'])
        ->assertDontSee('wire:model="notas.', false)
        ->assertDontSee('Guardar notas');
});

it('shows the grades read-only once the acta is validated', function (): void {
    foreach ([$this->ana, $this->luis] as $matricula) {
        foreach ([$this->parcial, $this->oral] as $componente) {
            NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $matricula->id, 'componente_id' => $componente->id, 'nota' => '15.00', 'registrado_por' => $this->usuario->id]);
        }
    }
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    app(CerrarActa::class)($this->grupo->id, $this->docente->id, $this->usuario);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSee('El acta final del grupo ING-01-A ya fue validada; sus notas parciales ya no se modifican.')
        ->assertDontSee('wire:model="notas.', false)
        ->assertDontSee('Guardar notas');
});

it('offers the pre-acta PDF only while the acta is open, since a closed acta has its own PDF', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertActionVisible('descargarPreActa');

    foreach ([$this->ana, $this->luis] as $matricula) {
        foreach ([$this->parcial, $this->oral] as $componente) {
            NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $matricula->id, 'componente_id' => $componente->id, 'nota' => '15.00', 'registrado_por' => $this->usuario->id]);
        }
    }
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    app(CerrarActa::class)($this->grupo->id, $this->docente->id, $this->usuario);

    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertActionHidden('descargarPreActa');
});

it('clears the previous rejection once the corrected grades are saved', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->luis->id}.{$this->parcial->id}", '25')
        ->call('guardar')
        ->assertHasErrors('notas')
        ->set("notas.{$this->luis->id}.{$this->parcial->id}", '16')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Notas parciales guardadas.')
        ->assertDontSee('La nota 25 no es válida');
});
