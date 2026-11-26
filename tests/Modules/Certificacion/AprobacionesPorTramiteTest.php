<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Certificacion\Contracts\AprobacionesPorReclasificacion;
use App\Modules\Certificacion\Contracts\AprobacionesPorSuficiencia;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Models\Aprobacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->ingles = ActasFinales::planDeEstudios('Inglés', [4, 4, 4]);
    $this->persona = Persona::factory()->create();
    $this->usuario = User::factory()->create();
});

function cicloDelPlan(int $numero): Ciclo
{
    return Ciclo::query()->where(['idioma_id' => test()->ingles->id, 'numero' => $numero])->sole();
}

/**
 * @return list<array{int, string, ?string}>
 */
function aprobacionesPorTramite(int $tramiteId): array
{
    return Aprobacion::query()->where(['origen_tipo' => 'tramite', 'origen_id' => $tramiteId])->orderBy('ciclo_numero')->get()
        ->map(fn (Aprobacion $fila): array => [$fila->ciclo_numero, $fila->forma->value, $fila->nota])->all();
}

// Transaction level of every query the callback runs after its first one, above the test's own transaction.
function nivelesDeTransaccion(Closure $operacion): array
{
    $base = DB::transactionLevel();
    $niveles = [];
    DB::listen(function (QueryExecuted $consulta) use (&$niveles, $base): void {
        $niveles[] = $consulta->connection->transactionLevel() - $base;
    });
    $operacion();

    return array_values(array_unique($niveles));
}

it('records a reclassification skipping the cycles an acta already graded, and a retry records nothing more', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[2, '16.00', 'aprobado']]);
    $reclasificar = fn (): int => app(AprobacionesPorReclasificacion::class)($this->persona->id, cicloDelPlan(5)->id, 901, $this->usuario->id);

    expect($reclasificar())->toBe(3)
        ->and($reclasificar())->toBe(0)
        ->and(aprobacionesPorTramite(901))->toBe([[1, 'clasificacion', null], [3, 'clasificacion', null], [4, 'clasificacion', null]]);
});

it('reads the graded cycles and records the reclassification in one transaction', function (): void {
    $cicloId = cicloDelPlan(3)->id;
    $niveles = nivelesDeTransaccion(fn (): int => app(AprobacionesPorReclasificacion::class)($this->persona->id, $cicloId, 902, $this->usuario->id));

    expect($niveles)->not->toContain(0)
        ->and(aprobacionesPorTramite(902))->toHaveCount(2);
});

it('records a passed proficiency exam for the active cycles of the level only, with its grade, and a retry records nothing more', function (): void {
    cicloDelPlan(3)->update(['activo' => false]);
    ActasFinales::historial($this->persona, 'Inglés', [[2, '17.00', 'aprobado']]);
    $suficiencia = fn (): int => app(AprobacionesPorSuficiencia::class)($this->persona->id, 'Inglés', NivelCurricular::Basico, '15.00', 903, $this->usuario->id);

    expect($suficiencia())->toBe(2)
        ->and($suficiencia())->toBe(0)
        ->and(aprobacionesPorTramite(903))->toBe([[1, FormaDeAprobacion::Suficiencia->value, '15.00'], [4, FormaDeAprobacion::Suficiencia->value, '15.00']])
        ->and(Aprobacion::query()->where('nivel', NivelCurricular::Intermedio->value)->exists())->toBeFalse();
});

it('reads the graded cycles and records the proficiency exam in one transaction', function (): void {
    $niveles = nivelesDeTransaccion(fn (): int => app(AprobacionesPorSuficiencia::class)($this->persona->id, 'Inglés', NivelCurricular::Basico, '14.00', 904, $this->usuario->id));

    expect($niveles)->not->toContain(0)
        ->and(aprobacionesPorTramite(904))->toHaveCount(4);
});
