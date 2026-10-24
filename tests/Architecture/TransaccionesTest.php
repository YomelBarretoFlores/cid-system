<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

function archivosDeLaAplicacion(string $subcarpeta = 'app'): Collection
{
    $raiz = dirname(__DIR__, 2);

    return collect(Finder::create()->files()->in("{$raiz}/{$subcarpeta}")->name('*.php'))
        ->mapWithKeys(fn ($archivo): array => [str_replace("{$raiz}/", '', $archivo->getRealPath()) => $archivo->getContents()]);
}

it('runs every transaction of the application through the shared helper', function (): void {
    $directas = archivosDeLaAplicacion()
        ->filter(fn (string $codigo): bool => preg_match('/DB::(transaction|beginTransaction)\(|->(transaction|beginTransaction)\(/', $codigo) === 1)
        ->keys()
        ->reject(fn (string $ruta): bool => $ruta === 'app/Shared/Support/Database/Transaccion.php')
        ->values()
        ->all();

    expect($directas)->toBe([]);
});

it('never retries a panel transaction, which changes a record loaded before it', function (): void {
    $conReintento = archivosDeLaAplicacion()
        ->filter(fn (string $codigo, string $ruta): bool => str_contains($ruta, '/Filament/') && preg_match('/Transaccion::ejecutar\((?![^;]*reintentar: false)[^;]*;/s', $codigo) === 1)
        ->keys()
        ->values()
        ->all();

    expect($conReintento)->toBe([]);
});
