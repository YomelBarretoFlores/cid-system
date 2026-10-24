<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\ReservarVacante;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->alumno = User::factory()->create();
    $this->ficha = Persona::factory()->create(['user_id' => $this->alumno->id]);
    $this->fichaOtra = Persona::factory()->create(['user_id' => User::factory()]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->ciclo1 = Ciclo::factory()->for($this->ingles)->create(['numero' => 1]);
    $this->ciclo2 = Ciclo::factory()->for($this->ingles)->create(['numero' => 2]);
    $this->abierto = Periodo::factory()->conMatriculaAbierta()->create();
    $this->grupo1 = Grupo::factory()->for($this->abierto)->for($this->ciclo1)->create(['codigo' => 'SON-I-A', 'cupo_maximo' => 10]);
    $this->grupo2 = Grupo::factory()->for($this->abierto)->for($this->ciclo2)->create(['codigo' => 'SON-II-A', 'cupo_maximo' => 10]);
    $concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    $this->pagoPropio = Pago::factory()->validado()->for($concepto)->create(['persona_id' => $this->ficha->id]);
    $this->pagoAjeno = Pago::factory()->validado()->for($concepto)->create(['persona_id' => $this->fichaOtra->id]);
});

/** @return array{int, int, int} */
function sondaEstadoMatricula(): array
{
    return [Matricula::query()->count(), (int) Grupo::query()->sum('matriculados'), Pago::query()->count()];
}

it('refuses a tampered payment of another student and enrolls nothing', function (): void {
    $antes = sondaEstadoMatricula();

    Livewire::actingAs($this->alumno)->test(Matricularse::class)
        ->set('grupo', (string) $this->grupo1->id)->set('pago', (string) $this->pagoAjeno->id)
        ->call('matricularme')->assertHasErrors(['pago' => 'in']);

    expect(sondaEstadoMatricula())->toBe($antes);
});

it('refuses a tampered group outside the offer and enrolls nothing', function (): void {
    $cerrado = Grupo::factory()->for(Periodo::factory()->create())->for($this->ciclo1)->create(['codigo' => 'SON-I-Z']);
    $antes = sondaEstadoMatricula();

    foreach ([$this->grupo2, $cerrado] as $grupo) {
        Livewire::actingAs($this->alumno)->test(Matricularse::class)
            ->set('grupo', (string) $grupo->id)->set('pago', (string) $this->pagoPropio->id)
            ->call('matricularme')->assertHasErrors(['grupo' => 'in']);
    }

    expect(sondaEstadoMatricula())->toBe($antes);
});

it('offers the enabled cycle instead of cycle I, and cycle I of a language without enablement', function (): void {
    Habilitacion::factory()->create(['persona_id' => $this->ficha->id, 'ciclo_id' => $this->ciclo2->id]);
    $frances = Idioma::factory()->create(['nombre' => 'Francés']);
    Grupo::factory()->for($this->abierto)->for(Ciclo::factory()->for($frances)->state(['numero' => 1]))->create(['codigo' => 'FRA-I-A']);
    Grupo::factory()->for($this->abierto)->for(Ciclo::factory()->for($frances)->state(['numero' => 2]))->create(['codigo' => 'FRA-II-A']);
    $inactivo = Idioma::factory()->create(['nombre' => 'Quechua', 'activo' => false]);
    Grupo::factory()->for($this->abierto)->for(Ciclo::factory()->for($inactivo)->state(['numero' => 1]))->create(['codigo' => 'QUE-I-A']);

    $this->actingAs($this->alumno)->get(route('estudiante.matricula'))->assertOk()
        ->assertSee('SON-II-A')->assertDontSee('SON-I-A')
        ->assertSee('FRA-I-A')->assertDontSee('FRA-II-A')->assertDontSee('QUE-I-A');
});

it('hides groups that are not open or whose enrollment window is over', function (): void {
    $this->grupo1->forceFill(['estado' => EstadoGrupo::Cerrado])->save();
    $hoy = CarbonImmutable::today('America/Lima');
    $vencido = Periodo::factory()->conMatriculaAbierta()->create(['matricula_inicio' => $hoy->subDays(10)->toDateString(), 'matricula_fin' => $hoy->subDay()->toDateString()]);
    Grupo::factory()->for($vencido)->for($this->ciclo1)->create(['codigo' => 'SON-I-V']);

    $this->actingAs($this->alumno)->get(route('estudiante.matricula'))->assertOk()
        ->assertDontSee('SON-I-A')->assertDontSee('SON-I-V');
});

it('keeps the ReservarVacante messages for a closed and a full group', function (): void {
    $this->grupo1->forceFill(['estado' => EstadoGrupo::Cerrado])->save();
    $this->grupo2->forceFill(['matriculados' => 10])->save();

    expect(fn () => app(ReservarVacante::class)($this->grupo1->id))->toThrow(ReglaDeNegocioIncumplida::class, 'La matrícula de este grupo no está abierta.')
        ->and(fn () => app(ReservarVacante::class)($this->grupo2->id))->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo no tiene vacantes disponibles.')
        ->and($this->grupo2->refresh()->matriculados)->toBe(10);
});

it('shows only the student own enrollments', function (): void {
    Matricula::factory()->create(['persona_id' => $this->fichaOtra->id, 'grupo_id' => $this->grupo2->id, 'pago_id' => $this->pagoAjeno->id]);
    Matricula::factory()->create(['persona_id' => $this->ficha->id, 'grupo_id' => $this->grupo1->id, 'pago_id' => $this->pagoPropio->id]);

    $this->actingAs($this->alumno)->get(route('estudiante.matriculas'))->assertOk()
        ->assertSee('SON-I-A')->assertDontSee('SON-II-A');
});
