<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;

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
    $final = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40.00', 'orden' => 2]);
    $parcial = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '60.00', 'orden' => 1]);

    $resultado = app(EsquemaDelGrupo::class)($this->grupo->id);

    expect([$resultado->esquemaId, $resultado->nombre, $resultado->notaMaxima, $resultado->notaMinimaAprobatoria])->toBe([$esquema->id, 'Inglés intermedio', '20.00', '13.00'])
        ->and($resultado->componentes)->toEqual([
            new ComponenteDeEvaluacion($parcial->id, 'parcial', 'Examen parcial', '60.00'),
            new ComponenteDeEvaluacion($final->id, 'final', 'Examen final', '40.00'),
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

it('returns null when no scheme applies', function (): void {
    esquemaDePrueba('Otro nivel', ['nivel' => NivelCurricular::Basico]);

    expect(app(EsquemaDelGrupo::class)($this->grupo->id))->toBeNull();
});
