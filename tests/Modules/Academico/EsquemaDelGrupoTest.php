<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;

beforeEach(function (): void {
    $this->ingles = Idioma::factory()->create();
    $ciclo = Ciclo::factory()->for($this->ingles)->create(['nivel' => NivelCurricular::Intermedio]);
    $this->grupo = Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'periodo_id' => Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25'])->id]);
});

function esquemaDePrueba(string $nombre, array $atributos = []): EsquemaEvaluacion
{
    return EsquemaEvaluacion::factory()->create(['nombre' => $nombre, 'vigente_desde' => '2027-01-01', ...$atributos]);
}

it('gives the scheme of the language and level with its components in order', function (): void {
    $esquema = esquemaDePrueba('Inglés intermedio', ['idioma_id' => $this->ingles->id, 'nivel' => NivelCurricular::Intermedio, 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '13.00']);
    $final = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'orden' => 2]);
    $oral = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'nombre' => 'Expresión oral', 'orden' => 1]);
    $nota = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral' => 30, 'final' => 70], 'ponderado', 'entero', 1)->create(['codigo' => 'nota', 'nombre' => 'Nota final', 'orden' => 3]);

    $resultado = app(EsquemaDelGrupo::class)($this->grupo->id);

    expect([$resultado->esquemaId, $resultado->nombre, $resultado->notaMaxima, $resultado->notaMinimaAprobatoria])->toBe([$esquema->id, 'Inglés intermedio', '20.00', '13.00'])
        ->and($resultado->componentes)->toEqual([
            new ComponenteDeEvaluacion($oral->id, 'oral', 'Expresión oral'),
            new ComponenteDeEvaluacion($final->id, 'final', 'Examen final'),
            new ComponenteDeEvaluacion($nota->id, 'nota', 'Nota final', TipoDeComponente::Calculada, CalculoDeComponente::Ponderado, RedondeoDeNota::Entero, [['peso' => 30, 'codigo' => 'oral'], ['peso' => 70, 'codigo' => 'final']], 1),
        ]);
});

it('prefers the most specific scheme valid on the period start', function (array $esquemas, string $esperado): void {
    foreach ($esquemas as [$nombre, $atributos]) {
        esquemaDePrueba($nombre, array_map(fn (mixed $valor): mixed => match ($valor) {
            'ingles' => $this->ingles->id,
            'otro' => Idioma::factory()->create()->id,
            default => $valor,
        }, $atributos));
    }

    expect(app(EsquemaDelGrupo::class)($this->grupo->id)?->nombre)->toBe($esperado);
})->with([
    'language and level over language only' => [[['Solo idioma', ['idioma_id' => 'ingles']], ['Idioma y nivel', ['idioma_id' => 'ingles', 'nivel' => NivelCurricular::Intermedio]]], 'Idioma y nivel'],
    'language only over level only' => [[['Solo nivel', ['nivel' => NivelCurricular::Intermedio]], ['Solo idioma', ['idioma_id' => 'ingles']]], 'Solo idioma'],
    'level only over general' => [[['General', []], ['Solo nivel', ['nivel' => NivelCurricular::Intermedio]]], 'Solo nivel'],
    'latest valid among equals' => [[['Antiguo', ['vigente_desde' => '2026-01-01']], ['Reciente', ['vigente_desde' => '2027-02-01']]], 'Reciente'],
    'ignores one not yet valid' => [[['General', []], ['Futuro', ['idioma_id' => 'ingles', 'vigente_desde' => '2027-03-02']]], 'General'],
    'ignores one already expired' => [[['General', []], ['Vencido', ['idioma_id' => 'ingles', 'vigente_desde' => '2026-01-01', 'vigente_hasta' => '2027-02-28']]], 'General'],
    'ignores another language and level' => [[['General', []], ['Otro nivel', ['nivel' => NivelCurricular::Avanzado]], ['Otro idioma', ['idioma_id' => 'otro']]], 'General'],
]);

it('gives an accelerated group of a program the scheme of that program and regime before a general one', function (): void {
    $posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();
    $ninos = Programa::query()->where('codigo', 'NINOS')->sole();
    esquemaDePrueba('General', ['idioma_id' => $this->ingles->id]);
    esquemaDePrueba('Acelerado para todos', ['regimen' => 'acelerado']);
    esquemaDePrueba('Acelerado de posgrado', ['programa_id' => $posgrado->id, 'regimen' => 'acelerado']);
    esquemaDePrueba('Niños', ['programa_id' => $ninos->id]);
    $siguiente = Ciclo::factory()->for($this->ingles)->create(['nivel' => NivelCurricular::Intermedio, 'numero' => $this->grupo->ciclo->numero + 1]);
    $acelerado = fn (int $programaId): Grupo => Grupo::factory()->create([
        'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id, 'programa_id' => $programaId, 'es_acelerado' => true, 'ciclo_hasta_id' => $siguiente->id,
    ]);
    $regularDeNinos = Grupo::factory()->create(['ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id, 'programa_id' => $ninos->id]);

    expect(app(EsquemaDelGrupo::class)($acelerado($posgrado->id)->id)?->nombre)->toBe('Acelerado de posgrado')
        ->and(app(EsquemaDelGrupo::class)($acelerado(Programa::query()->where('codigo', 'JA')->value('id'))->id)?->nombre)->toBe('Acelerado para todos')
        ->and(app(EsquemaDelGrupo::class)($regularDeNinos->id)?->nombre)->toBe('Niños')
        ->and(app(EsquemaDelGrupo::class)($this->grupo->id)?->nombre)->toBe('General');
});

it('returns null when no scheme applies', function (): void {
    esquemaDePrueba('Otro nivel', ['nivel' => NivelCurricular::Basico]);

    expect(app(EsquemaDelGrupo::class)($this->grupo->id))->toBeNull();
});
