<?php

declare(strict_types=1);

use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Support\ErroresDePostgres;

it('turns a statement that runs past the limit into a message and keeps the outer transaction usable', function (): void {
    Log::spy();

    expect(fn () => Transaccion::ejecutar(function (): void {
        DB::statement("set local statement_timeout = '100ms'");
        DB::select('select pg_sleep(1)');
    }))->toThrow(ReglaDeNegocioIncumplida::class, 'La operación tardó demasiado y se canceló. Inténtalo de nuevo; si se repite, avisa a soporte.')
        ->and(DB::selectOne('select 1 as uno')->uno)->toBe(1);

    Log::shouldHaveReceived('warning')->with('Operación cancelada por tiempo de ejecución.', ['sqlstate' => '57014'])->once();
});

it('leaves a deadlock inside an outer transaction to the outermost one, which is the only place a retry is safe', function (): void {
    $intentos = 0;

    expect(function () use (&$intentos): void {
        Transaccion::ejecutar(function () use (&$intentos): void {
            $intentos++;

            throw ErroresDePostgres::conCodigo('40P01', 'deadlock detected');
        });
    })->toThrow(DeadlockException::class)
        ->and($intentos)->toBe(1);
});
