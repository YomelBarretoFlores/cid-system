<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;
use Tests\Support\ErroresDePostgres;

it('retries the victim of a real deadlock so both transactions complete', function (): void {
    $a = Grupo::factory()->create(['matriculados' => 0]);
    $b = Grupo::factory()->create(['matriculados' => 0]);

    expect(enParalelo('bloqueo-cruzado.php', [[$a->id, $b->id], [$b->id, $a->id]]))->toBe(['hecho', 'hecho'])
        ->and([$a->refresh()->matriculados, $b->refresh()->matriculados])->toBe([2, 2]);
});

it('gives up after three attempts with a message the user can act on', function (string $sqlstate): void {
    Log::spy();
    $intentos = 0;

    expect(function () use (&$intentos, $sqlstate): void {
        Transaccion::ejecutar(function () use (&$intentos, $sqlstate): void {
            $intentos++;

            throw ErroresDePostgres::conCodigo($sqlstate, 'deadlock detected');
        });
    })->toThrow(ReglaDeNegocioIncumplida::class, 'Otra operación está modificando estos datos en este momento. Inténtalo de nuevo en unos segundos.')
        ->and($intentos)->toBe(3);

    Log::shouldHaveReceived('warning')->with('Transacción reintentada tras un conflicto de concurrencia.', Mockery::on(fn (array $contexto): bool => $contexto['sqlstate'] === $sqlstate))->twice();
    Log::shouldHaveReceived('warning')->with('Operación rechazada por un conflicto de concurrencia.', ['sqlstate' => $sqlstate])->once();
})->with(['deadlock' => '40P01', 'serialization failure' => '40001']);

it('turns a lock wait beyond the limit into a message instead of waiting forever', function (): void {
    $grupo = Grupo::factory()->create();

    expect(enParalelo('retener-o-esperar.php', [['retener', $grupo->id], ['esperar', $grupo->id]]))
        ->toBe(['esperar: Otra operación está modificando estos datos en este momento. Inténtalo de nuevo en unos segundos.', 'retener: hecho']);
});

it('returns the result of the operation and does not retry a business rule', function (): void {
    $intentos = 0;

    expect(Transaccion::ejecutar(function () use (&$intentos): string {
        $intentos++;

        return 'listo';
    }))->toBe('listo');

    $conRegla = function () use (&$intentos): void {
        Transaccion::ejecutar(function () use (&$intentos): void {
            $intentos++;

            throw new ReglaDeNegocioIncumplida('Regla');
        });
    };

    expect($conRegla)->toThrow(ReglaDeNegocioIncumplida::class, 'Regla')
        ->and($intentos)->toBe(2);
});

it('retries from the outermost transaction when the deadlock happens in a nested one', function (): void {
    $intentos = 0;

    // A by-reference closure, not an arrow function: an arrow function would copy the counter on every attempt.
    $resultado = Transaccion::ejecutar(function () use (&$intentos): string {
        return Transaccion::ejecutar(function () use (&$intentos): string {
            $intentos++;

            if ($intentos === 1) {
                throw ErroresDePostgres::conCodigo('40P01', 'deadlock detected');
            }

            return 'listo';
        });
    });

    expect([$resultado, $intentos])->toBe(['listo', 2]);
});
