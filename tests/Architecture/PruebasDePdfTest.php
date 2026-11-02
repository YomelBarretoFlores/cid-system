<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

// PdfBuilder::contains() with a list passes as soon as one text matches, so a list of expected texts proves only one of them.
it('checks every expected text of a PDF one by one instead of passing a list to contains', function (): void {
    $raiz = dirname(__DIR__, 2);

    $conLista = collect(Finder::create()->files()->in("{$raiz}/tests")->name('*.php'))
        ->filter(fn ($archivo): bool => preg_match('/->contains\(\s*\[/', $archivo->getContents()) === 1)
        ->map(fn ($archivo): string => str_replace("{$raiz}/", '', $archivo->getRealPath()))
        ->reject(fn (string $ruta): bool => $ruta === 'tests/Architecture/PruebasDePdfTest.php')
        ->values()
        ->all();

    expect($conLista)->toBe([]);
});
