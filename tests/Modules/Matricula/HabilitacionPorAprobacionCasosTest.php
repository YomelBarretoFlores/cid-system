<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Events\ActaCerrada;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->cicloUno = $this->grupo->ciclo;
    $this->idiomaId = $this->cicloUno->idioma_id;
    [$oral, $parcial, $final] = [$this->curso['oral']->id, $this->curso['parcial']->id, $this->curso['final']->id];
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo');
    $this->ciro = ActasFinales::estudiante($this->grupo, 'Castro');
    ActasFinales::preActa($this->ana, [$oral => '15.00', $parcial => '15.00', $final => '14.00']);
    ActasFinales::preActa($this->beto, [$parcial => '8.00', $final => '10.00']);
});

function cerrarActaParaHabilitar(): void
{
    app(CerrarActa::class)(test()->grupo->id, test()->curso['docente']->id, test()->curso['usuario']);
}

function estadoDeHabilitaciones(): array
{
    return [
        'filas' => DB::table('mat_habilitaciones')->orderBy('id')->get()->map(fn ($fila): array => (array) $fila)->all(),
        'auditoria' => DB::table('aud_registros')->where('tabla', 'mat_habilitaciones')->count(),
    ];
}

it('lets a student who passed ciclo 1 enroll in a ciclo 2 group, and the portal offers it', function (): void {
    $cicloDos = Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico II']);
    $periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $grupoDos = Grupo::factory()->for($periodo)->for($cicloDos)->create(['codigo' => 'ING-2-PROBE', 'cupo_maximo' => 20]);
    $grupoUnoNuevo = Grupo::factory()->for($periodo)->for($this->cicloUno)->create(['codigo' => 'ING-1-PROBE', 'cupo_maximo' => 20]);
    $user = User::factory()->create();
    Persona::query()->whereKey($this->ana->persona_id)->update(['user_id' => $user->id]);
    $concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    $pago = Pago::factory()->validado()->for($concepto)->create(['persona_id' => $this->ana->persona_id]);

    expect(fn () => app(MatricularEstudiante::class)($this->ana->persona_id, $grupoDos->id, $pago->id))
        ->toThrow(ReglaDeNegocioIncumplida::class);

    cerrarActaParaHabilitar();

    Livewire::actingAs($user)->test(Matricularse::class)
        ->assertSee('ING-2-PROBE')
        ->assertDontSee('ING-1-PROBE');

    $matricula = app(MatricularEstudiante::class)($this->ana->persona_id, $grupoDos->id, $pago->id);

    expect($matricula->grupo_id)->toBe($grupoDos->id)
        ->and($matricula->condicion)->toBe(CondicionMatricula::Regular);
});

it('gives nothing to failing or NSP students, keeps a higher placement, and moves a lower placement forward', function (): void {
    $cicloDos = Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico]);
    $cicloCuatro = Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 4, 'nivel' => NivelCurricular::Basico]);
    $dany = ActasFinales::estudiante($this->grupo, 'Diaz');
    ActasFinales::preActa($dany, [$this->curso['oral']->id => '18.00', $this->curso['parcial']->id => '18.00', $this->curso['final']->id => '18.00']);
    $eva = ActasFinales::estudiante($this->grupo, 'Espinoza');
    ActasFinales::preActa($eva, [$this->curso['oral']->id => '18.00', $this->curso['parcial']->id => '18.00', $this->curso['final']->id => '18.00']);
    Habilitacion::factory()->create(['persona_id' => $dany->persona_id, 'idioma_id' => $this->idiomaId, 'ciclo_id' => $this->cicloUno->id]);
    Habilitacion::factory()->create(['persona_id' => $eva->persona_id, 'idioma_id' => $this->idiomaId, 'ciclo_id' => $cicloCuatro->id]);

    cerrarActaParaHabilitar();

    $estados = DB::table('cer_notas_finales')->pluck('estado_final', 'persona_id')->all();
    expect($estados[$this->beto->persona_id])->toBe('desaprobado')
        ->and($estados[$this->ciro->persona_id])->toBe('nsp');

    $filas = Habilitacion::query()->get()->mapWithKeys(fn ($h): array => [$h->persona_id => [$h->ciclo_id, $h->origen->value]])->all();
    expect($filas)->toHaveCount(3)
        ->and($filas[$this->ana->persona_id])->toBe([$cicloDos->id, 'aprobacion'])
        ->and($filas[$dany->persona_id])->toBe([$cicloDos->id, 'aprobacion'])
        ->and($filas[$eva->persona_id])->toBe([$cicloCuatro->id, 'clasificacion'])
        ->and(array_key_exists((string) $this->beto->persona_id, $filas))->toBeFalse()
        ->and(array_key_exists((string) $this->ciro->persona_id, $filas))->toBeFalse();
});

it('writes nothing when the command runs again after the close', function (): void {
    Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico]);
    cerrarActaParaHabilitar();
    $antes = estadoDeHabilitaciones();
    expect($antes['filas'])->toHaveCount(1)->and($antes['auditoria'])->toBe(1);

    $this->travel(5)->minutes();
    $this->artisan('matricula:recalcular-habilitaciones')->expectsOutput('Habilitaciones actualizadas: 0')->assertSuccessful();
    $this->artisan('matricula:recalcular-habilitaciones')->expectsOutput('Habilitaciones actualizadas: 0')->assertSuccessful();

    expect(estadoDeHabilitaciones())->toBe($antes);
});

it('dispatches no event when a second close is rejected', function (): void {
    cerrarActaParaHabilitar();
    Event::fake([ActaCerrada::class]);

    expect(fn (): Acta => cerrarActaParaHabilitar())->toThrow(ReglaDeNegocioIncumplida::class, 'ya está cerrada');
    Event::assertNotDispatched(ActaCerrada::class);
});

it('advances to the next existing cycle when numbers are not contiguous', function (): void {
    $cicloCinco = Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 5, 'nivel' => NivelCurricular::Intermedio]);
    $cicloTres = Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 3, 'nivel' => NivelCurricular::Basico]);

    cerrarActaParaHabilitar();

    expect(Habilitacion::query()->sole())->ciclo_id->toBe($cicloTres->id)->persona_id->toBe($this->ana->persona_id);
});

it('leaves an existing row untouched when the student passes the last cycle', function (): void {
    Habilitacion::factory()->create(['persona_id' => $this->ana->persona_id, 'idioma_id' => $this->idiomaId, 'ciclo_id' => $this->cicloUno->id]);
    $antes = estadoDeHabilitaciones();

    cerrarActaParaHabilitar();

    expect(estadoDeHabilitaciones())->toBe($antes);
});

it('leaves the habilitations unchanged when the close is refused outside the grade calendar', function (): void {
    Ciclo::factory()->create(['idioma_id' => $this->idiomaId, 'numero' => 2, 'nivel' => NivelCurricular::Basico]);
    Habilitacion::factory()->create(['persona_id' => $this->beto->persona_id, 'idioma_id' => $this->idiomaId, 'ciclo_id' => $this->cicloUno->id]);
    $antes = estadoDeHabilitaciones();
    $this->travelTo(CarbonImmutable::parse('2027-04-10 10:00', 'America/Lima'));

    expect(fn (): Acta => cerrarActaParaHabilitar())->toThrow(ReglaDeNegocioIncumplida::class, 'se valida del');

    expect(estadoDeHabilitaciones())->toBe($antes);
});

it('advances one step per passed cycle when the command reads a full history', function (): void {
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Francés', [[1, '14.00', 'aprobado'], [2, '15.00', 'aprobado'], [3, '09.00', 'desaprobado'], [4, null, 'nsp']]);
    $idiomaId = Idioma::query()->latest('id')->value('id');
    $cicloTres = Ciclo::query()->where(['idioma_id' => $idiomaId, 'numero' => 3])->value('id');

    $this->artisan('matricula:recalcular-habilitaciones')->assertSuccessful();

    expect(Habilitacion::query()->where('persona_id', $persona->id)->sole())->ciclo_id->toBe($cicloTres);
});
