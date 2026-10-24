<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('runs every transaction of the application through the shared helper', function (): void {
    $raiz = dirname(__DIR__, 2);
    $directas = collect(Finder::create()->files()->in("{$raiz}/app")->name('*.php'))
        ->filter(fn ($archivo): bool => str_contains($archivo->getContents(), 'DB::transaction('))
        ->map(fn ($archivo): string => str_replace("{$raiz}/", '', $archivo->getRealPath()))
        ->reject(fn (string $ruta): bool => $ruta === 'app/Shared/Support/Database/Transaccion.php')
        ->values()
        ->all();

    expect($directas)->toBe([]);
});
