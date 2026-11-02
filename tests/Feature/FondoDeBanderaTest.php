<?php

declare(strict_types=1);

use App\Shared\Ui\BanderaDeIdioma;

it('draws every flag as a standalone SVG image that can fill the letters of a word', function (BanderaDeIdioma $bandera): void {
    expect($bandera->fondo())->toStartWith('url(data:image/svg+xml;base64,')->toEndWith(')');

    $svg = base64_decode(substr($bandera->fondo(), strlen('url(data:image/svg+xml;base64,'), -1), true);

    expect($svg)->toStartWith('<svg xmlns="http://www.w3.org/2000/svg"')
        ->not->toContain('var(')
        ->and(simplexml_load_string((string) $svg))->not->toBeFalse();
})->with(BanderaDeIdioma::cases());
