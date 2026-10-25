<?php

declare(strict_types=1);

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Notas\Nota;

it('reads a grade with up to two decimals, a comma as decimal point and blank as empty', function (mixed $valor, ?string $esperado): void {
    expect(Nota::leer($valor, '20.00'))->toBe($esperado);
})->with([
    'integer text' => ['15', '15.00'],
    'comma' => ['12,5', '12.50'],
    'two decimals' => [' 9.75 ', '9.75'],
    'the maximum' => ['20', '20.00'],
    'zero' => ['0', '0.00'],
    'integer' => [18, '18.00'],
    'blank' => ['  ', null],
    'null' => [null, null],
]);

it('refuses grades outside the scale or with more than two decimals', function (mixed $valor, string $mensaje): void {
    expect(fn (): ?string => Nota::leer($valor, '20.00'))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'above the maximum' => ['20.01', 'La nota 20.01 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'three decimals' => ['12.345', 'La nota 12.345 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'negative' => ['-1', 'La nota -1 no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'text' => ['abc', 'La nota abc no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
    'array' => [['15'], 'Una de las notas no es válida: usa un número de 0 a 20 con hasta dos decimales.'],
]);

it('converts grades to and from hundredths', function (): void {
    expect(Nota::centesimos('12.5'))->toBe(1250)
        ->and(Nota::centesimos('7'))->toBe(700)
        ->and(Nota::deCentesimos(1475))->toBe('14.75')
        ->and(Nota::deCentesimos(5))->toBe('0.05');
});
