<?php

declare(strict_types=1);

use App\Shared\Ui\PaletaCiln;

it('keeps the Filament palettes equal to the shared design tokens', function (string $nombre, array $paleta): void {
    preg_match_all("/--color-{$nombre}-(\\d+): ([^;]+);/", (string) file_get_contents(dirname(__DIR__, 2).'/resources/css/ciln/tokens.css'), $tokens);

    expect(array_combine(array_map(intval(...), $tokens[1]), $tokens[2]))->toBe($paleta);
})->with([
    ['piedra', PaletaCiln::PIEDRA],
    ['oro', PaletaCiln::ORO],
    ['curso', PaletaCiln::CURSO],
]);
