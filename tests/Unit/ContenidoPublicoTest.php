<?php

declare(strict_types=1);

use App\Shared\Support\Cache\ContenidoPublico;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('never keeps content that was built while the page was being renewed', function (): void {
    $construida = ContenidoPublico::recordar('2026-10-24', function (): array {
        // A change commits while this visit is still building from the old rows.
        ContenidoPublico::olvidar();

        return ['version' => 'vieja'];
    });

    expect($construida)->toBe(['version' => 'vieja'])
        ->and(ContenidoPublico::recordar('2026-10-24', fn (): array => ['version' => 'nueva']))->toBe(['version' => 'nueva'])
        ->and(ContenidoPublico::recordar('2026-10-24', fn (): array => ['version' => 'otra']))->toBe(['version' => 'nueva']);
});

it('builds the page directly and logs a warning when the cache store fails', function (): void {
    Cache::shouldReceive('get')->andThrow(new RuntimeException('almacén caído'));
    Cache::shouldReceive('lock')->andThrow(new RuntimeException('almacén caído'));
    Cache::shouldReceive('forever')->andThrow(new RuntimeException('almacén caído'));
    Log::spy();

    expect(ContenidoPublico::recordar('2026-10-24', fn (): array => ['portada' => 'directa']))->toBe(['portada' => 'directa']);
    ContenidoPublico::olvidar();

    Log::shouldHaveReceived('warning')->with('No se pudo usar la caché de la portada pública.', ['error' => RuntimeException::class])->twice();
});

it('lets an error of the page builder through instead of building twice', function (): void {
    $intentos = 0;

    expect(function () use (&$intentos): void {
        ContenidoPublico::recordar('2026-10-24', function () use (&$intentos): array {
            $intentos++;

            throw new LogicException('falla la consulta de la oferta');
        });
    })->toThrow(LogicException::class, 'falla la consulta de la oferta')
        ->and($intentos)->toBe(1);
});

it('serves the built page once when only storing it in the cache fails', function (): void {
    Cache::shouldReceive('get')->andReturn(null);
    Cache::shouldReceive('lock')->andReturn(new class
    {
        public function block(int $segundos, Closure $tarea): mixed
        {
            return $tarea();
        }
    });
    Cache::shouldReceive('put')->andThrow(new RuntimeException('elemento demasiado grande'));
    Log::spy();
    $armados = 0;

    expect(ContenidoPublico::recordar('2026-10-24', function () use (&$armados): array {
        $armados++;

        return ['portada' => 'armada'];
    }))->toBe(['portada' => 'armada'])
        ->and($armados)->toBe(1);
    Log::shouldHaveReceived('warning')->once();
});

it('builds the page without a cache warning when the rebuild lock is still busy', function (): void {
    Cache::shouldReceive('get')->andReturn(null);
    Cache::shouldReceive('lock')->andReturn(new class
    {
        public function block(int $segundos, Closure $tarea): mixed
        {
            throw new LockTimeoutException;
        }
    });
    Log::spy();

    expect(ContenidoPublico::recordar('2026-10-24', fn (): array => ['portada' => 'directa']))->toBe(['portada' => 'directa']);
    Log::shouldNotHaveReceived('warning');
});
