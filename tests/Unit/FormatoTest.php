<?php

declare(strict_types=1);

use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

it('writes short dates as day, abbreviated Spanish month and year', function (string $fecha, string $esperado): void {
    expect(Formato::fechaCorta(CarbonImmutable::parse($fecha)))->toBe($esperado);
})->with([
    ['2026-10-01', '1 oct 2026'],
    ['2026-09-24', '24 set 2026'],
    ['2027-05-03', '3 may 2027'],
]);

it('writes grades without trailing zeros and a dash when missing', function (?string $nota, string $esperado): void {
    expect(Formato::nota($nota))->toBe($esperado);
})->with([
    ['15.50', '15.5'],
    ['40.00', '40'],
    ['12.25', '12.25'],
    ['7', '7'],
    [null, '—'],
]);

it('writes a date range within a month, across months and as a single day', function (string $desde, string $hasta, string $esperado): void {
    expect(Formato::rango(CarbonImmutable::parse($desde), CarbonImmutable::parse($hasta)))->toBe($esperado);
})->with([
    ['2026-10-06', '2026-10-31', '6 – 31 oct'],
    ['2026-09-28', '2026-10-05', '28 set – 5 oct'],
    ['2026-11-03', '2026-11-03', '3 nov'],
]);

it('writes long dates and cycle numbers the way the public page shows them', function (): void {
    expect(Formato::fechaLarga(CarbonImmutable::parse('2026-03-01')))->toBe('1 de marzo de 2026')
        ->and(Formato::diaDeMes(CarbonImmutable::parse('2026-10-31')))->toBe('31 de octubre')
        ->and(Formato::romano(9))->toBe('IX')
        ->and(Formato::romano(13))->toBe('13');
});

it('writes a grade in words, with its hundredths', function (?string $nota, string $esperado): void {
    expect(Formato::notaEnLetras($nota))->toBe($esperado);
})->with([
    ['15.59', 'Quince con cincuenta y nueve'],
    ['16.00', 'Dieciséis'],
    ['10.20', 'Diez con veinte'],
    ['14.75', 'Catorce con setenta y cinco'],
    ['11', 'Once'],
    ['20.00', 'Veinte'],
    ['0.00', 'Cero'],
    ['9.05', 'Nueve con cero cinco'],
    ['18.21', 'Dieciocho con veintiuno'],
    [null, '—'],
]);

it('writes amounts in words, feminine when they count hours', function (): void {
    expect(Formato::numeroEnLetras(240, femenino: true))->toBe('doscientas cuarenta')
        ->and(Formato::numeroEnLetras(60, femenino: true))->toBe('sesenta')
        ->and(Formato::numeroEnLetras(121, femenino: true))->toBe('ciento veintiuna')
        ->and(Formato::numeroEnLetras(100))->toBe('cien')
        ->and(Formato::numeroEnLetras(501))->toBe('quinientos uno')
        ->and(Formato::numeroEnLetras(1000))->toBe('1000');
});
