<?php

declare(strict_types=1);

use App\Shared\Ui\CodigoDeBarras;

it('encodes text as Code 128 B with its check symbol and the stop pattern', function (): void {
    expect(CodigoDeBarras::modulos('A'))->toBe('11010010000'.'10100011000'.'10001011000'.'1100011101011')
        ->and(CodigoDeBarras::modulos('01KX'))->toStartWith('11010010000')->toEndWith('1100011101011');
});

it('gives every symbol of the table its own pattern of eleven modules', function (): void {
    $patrones = new ReflectionClassConstant(CodigoDeBarras::class, 'PATRONES')->getValue();

    expect($patrones)->toHaveCount(106)
        ->and(array_unique($patrones))->toHaveCount(106)
        ->and(array_map(fn (string $patron): int => array_sum(str_split($patron)), $patrones))->each->toBe(11);
});

it('draws the barcode as a PNG image with quiet zones on both sides', function (): void {
    $uri = CodigoDeBarras::comoDataUri('01KX');
    $imagen = imagecreatefromstring((string) base64_decode(substr($uri, strlen('data:image/png;base64,')), true));

    expect($uri)->toStartWith('data:image/png;base64,')
        ->and(imagesx($imagen))->toBe((strlen(CodigoDeBarras::modulos('01KX')) + 20) * 2)
        ->and(imagecolorat($imagen, 0, 0))->toBe(imagecolorat($imagen, imagesx($imagen) - 1, 0));
});

it('refuses characters that Code 128 B cannot encode', function (): void {
    CodigoDeBarras::modulos('Ñandú');
})->throws(InvalidArgumentException::class);
