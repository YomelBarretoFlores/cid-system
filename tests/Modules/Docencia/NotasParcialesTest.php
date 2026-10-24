<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $ciclo = Ciclo::factory()->create(['nivel' => NivelCurricular::Basico]);
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Cerrado]);
    PortalDocente::asignar($this->grupo, $this->docente);
    $esquema = EsquemaEvaluacion::factory()->create(['idioma_id' => $ciclo->idioma_id, 'nota_maxima' => '20.00', 'vigente_desde' => '2027-01-01']);
    $this->parcial = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'orden' => 1]);
    $this->final = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'orden' => 2]);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id]);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id]);
});

function registrarNotas(array $notas, ?Docente $docente = null): void
{
    app(RegistrarNotasParciales::class)(test()->grupo->id, ($docente ?? test()->docente)->id, $notas, test()->usuario);
}

function notasGuardadas(): array
{
    return NotaParcial::query()->orderBy('matricula_id')->orderBy('componente_id')->get()
        ->map(fn (NotaParcial $nota): array => [$nota->matricula_id, $nota->componente_id, $nota->nota])->all();
}

it('records the grades per student and component, with the group and who recorded them, and leaves blanks out', function (): void {
    registrarNotas([
        $this->ana->id => [$this->parcial->id => '15', $this->final->id => ''],
        $this->luis->id => [$this->parcial->id => '12,5', $this->final->id => '20'],
    ]);

    expect(notasGuardadas())->toBe([
        [$this->ana->id, $this->parcial->id, '15.00'],
        [$this->luis->id, $this->parcial->id, '12.50'],
        [$this->luis->id, $this->final->id, '20.00'],
    ])
        ->and(NotaParcial::query()->pluck('grupo_id')->unique()->all())->toBe([$this->grupo->id])
        ->and(NotaParcial::query()->pluck('registrado_por')->unique()->all())->toBe([$this->usuario->id])
        ->and(DB::table('aud_registros')->where('tabla', 'doc_notas_parciales')->count())->toBe(3);
});

it('changes a grade, clears another and leaves the grades it did not receive untouched', function (): void {
    registrarNotas([$this->ana->id => [$this->parcial->id => '15', $this->final->id => '14'], $this->luis->id => [$this->parcial->id => '11']]);

    registrarNotas([$this->ana->id => [$this->parcial->id => '16.75', $this->final->id => ' '], $this->luis->id => [$this->parcial->id => '11.00']]);

    expect(notasGuardadas())->toBe([
        [$this->ana->id, $this->parcial->id, '16.75'],
        [$this->ana->id, $this->final->id, null],
        [$this->luis->id, $this->parcial->id, '11.00'],
    ])
        ->and(DB::table('aud_registros')->where('tabla', 'doc_notas_parciales')->where('operacion', 'UPDATE')->count())->toBe(2);
});

it('refuses grades that break a rule and saves nothing', function (Closure $preparar, string $mensaje): void {
    [$notas, $docente] = $preparar->call($this) + [[$this->ana->id => [$this->parcial->id => '15'], $this->luis->id => [$this->parcial->id => '18']], null];

    expect(fn () => registrarNotas($notas, $docente))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(NotaParcial::query()->count())->toBe(0);
})->with([
    'another teacher' => [fn (): array => [1 => Docente::factory()->create()], 'No tienes a cargo el grupo ING-01-A.'],
    'finished group' => [function (): array {
        $this->grupo->update(['estado' => EstadoGrupo::Finalizado]);

        return [];
    }, 'El grupo ING-01-A está Finalizado; las notas parciales solo se registran en grupos abiertos o cerrados.'],
    'no scheme' => [function (): array {
        EsquemaEvaluacion::query()->update(['vigente_desde' => '2027-03-02']);

        return [];
    }, 'El grupo ING-01-A no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.'],
    'above the maximum' => [fn (): array => [[$this->ana->id => [$this->parcial->id => '15'], $this->luis->id => [$this->parcial->id => '20.5']]], 'La nota 20.5 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'negative' => [fn (): array => [[$this->ana->id => [$this->parcial->id => '-1']]], 'La nota -1 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'three decimals' => [fn (): array => [[$this->ana->id => [$this->parcial->id => '14.125']]], 'La nota 14.125 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'text' => [fn (): array => [[$this->ana->id => [$this->parcial->id => 'AD']]], 'La nota AD no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'student of another group' => [fn (): array => [[Matricula::factory()->create()->id => [$this->parcial->id => '15']]], 'La lista de estudiantes del grupo cambió; vuelve a cargarla.'],
    'withdrawn student' => [function (): array {
        $this->luis->update(['estado' => 'retirada', 'retirado_en' => now()]);

        return [];
    }, 'La lista de estudiantes del grupo cambió; vuelve a cargarla.'],
    'grades that are not a list per component' => [fn (): array => [[$this->ana->id => '15']], 'El sistema de evaluación del grupo cambió; vuelve a cargar la página.'],
    'component of another scheme' => [fn (): array => [[$this->ana->id => [ComponenteEvaluacion::factory()->create()->id => '15']]], 'El sistema de evaluación del grupo cambió; vuelve a cargar la página.'],
]);

it('keeps a component with grades from being deleted', function (): void {
    registrarNotas([$this->ana->id => [$this->parcial->id => '15']]);

    expect(fn () => $this->parcial->delete())->toThrow(QueryException::class, 'doc_notas_parciales_componente_id_foreign');
});
