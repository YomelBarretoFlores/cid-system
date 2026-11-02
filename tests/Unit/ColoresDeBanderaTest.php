<?php

declare(strict_types=1);

use App\Shared\Ui\BanderaDeIdioma;

it('gives every flag at least two strong colors and never white, so a word painted with them stays readable', function (BanderaDeIdioma $bandera): void {
    expect(count($bandera->colores()))->toBeGreaterThanOrEqual(2)
        ->and($bandera->colores())->each->toMatch('/^#[0-9A-F]{6}$/')->not->toContain('#FFFFFF');
})->with(BanderaDeIdioma::cases());
