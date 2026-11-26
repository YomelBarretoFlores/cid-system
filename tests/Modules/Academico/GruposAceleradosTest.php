<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create();
    $this->aula = Aula::factory()->create(['capacidad' => 30]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->basicoUno = Ciclo::factory()->for($this->ingles)->create(['numero' => 1, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico I', 'horas_lectivas' => 60]);
    $this->basicoDos = Ciclo::factory()->for($this->ingles)->create(['numero' => 2, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico II', 'horas_lectivas' => 50]);
    $this->intermedioUno = Ciclo::factory()->for($this->ingles)->create(['numero' => 3, 'nivel' => NivelCurricular::Intermedio, 'nombre' => 'Intermedio I']);
});

function grupoAcelerado(Ciclo $ciclo, bool $esAcelerado = true): DatosDeGrupo
{
    return new DatosDeGrupo(
        periodoId: test()->periodo->id,
        cicloId: $ciclo->id,
        codigo: 'ING-AC',
        modalidad: Modalidad::Presencial,
        esAcelerado: $esAcelerado,
        cupoMaximo: 25,
        cupoMinimo: 10,
        programaId: Programa::query()->where('codigo', 'JA')->value('id'),
        aulaId: test()->aula->id,
    );
}

it('gives an accelerated group the next cycle of its level as its second cycle', function (): void {
    $grupo = app(GuardarGrupo::class)(grupoAcelerado($this->basicoUno));

    expect($grupo->refresh())
        ->es_acelerado->toBeTrue()
        ->ciclo_id->toBe($this->basicoUno->id)
        ->ciclo_hasta_id->toBe($this->basicoDos->id);
});

it('refuses an accelerated group on the last cycle of a level', function (): void {
    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoAcelerado($this->basicoDos)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un grupo acelerado cubre el ciclo elegido y el siguiente del mismo nivel; Básico II no tiene ciclo siguiente en su nivel.')
        ->and(Grupo::query()->count())->toBe(0);
});

it('refuses an accelerated group when the next cycle of the level is inactive', function (): void {
    $this->basicoDos->update(['activo' => false]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoAcelerado($this->basicoUno)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un grupo acelerado cubre el ciclo elegido y el siguiente del mismo nivel; Básico I no tiene ciclo siguiente en su nivel.');
});

it('keeps a regular group on a single cycle and drops the second cycle when a group stops being accelerated', function (): void {
    $regular = app(GuardarGrupo::class)(grupoAcelerado($this->basicoDos, esAcelerado: false));
    $acelerado = app(GuardarGrupo::class)(new DatosDeGrupo(...[...get_object_vars(grupoAcelerado($this->basicoUno)), 'codigo' => 'ING-AC2']));

    app(GuardarGrupo::class)(new DatosDeGrupo(...[...get_object_vars(grupoAcelerado($this->basicoUno, esAcelerado: false)), 'codigo' => 'ING-AC2']), $acelerado->id);

    expect($regular->refresh()->ciclo_hasta_id)->toBeNull()
        ->and($acelerado->refresh())->es_acelerado->toBeFalse()->ciclo_hasta_id->toBeNull();
});

it('describes the course of an accelerated group with both cycles and the hours of both', function (): void {
    $grupo = app(GuardarGrupo::class)(grupoAcelerado($this->basicoUno));

    expect(app(CursoDelGrupo::class)($grupo->id))
        ->cicloNumero->toBe(1)
        ->cicloNombre->toBe('Básico I')
        ->cicloNumeroHasta->toBe(2)
        ->cicloNombreHasta->toBe('Básico II')
        ->horasLectivasHasta->toBe(50)
        ->horasLectivas->toBe(110);
});

it('rejects at the database an accelerated group without a second cycle and a regular group with one', function (array $columnas): void {
    expect(fn () => Grupo::factory()->create(['ciclo_id' => $this->basicoUno->id, ...$columnas]))
        ->toThrow(QueryException::class, 'aca_grupos_ciclo_hasta_check');
})->with([
    'accelerated without second cycle' => [['es_acelerado' => true]],
    'regular with second cycle' => [fn (): array => ['es_acelerado' => false, 'ciclo_hasta_id' => test()->basicoDos->id]],
]);

it('rejects at the database a second cycle that is not the next one of the same level', function (): void {
    $grupo = Grupo::factory()->create(['ciclo_id' => $this->basicoDos->id]);

    expect(fn () => DB::table('aca_grupos')->where('id', $grupo->id)->update(['es_acelerado' => true, 'ciclo_hasta_id' => $this->intermedioUno->id]))
        ->toThrow(QueryException::class, 'aca_grupos: el segundo ciclo de un grupo acelerado');
});
