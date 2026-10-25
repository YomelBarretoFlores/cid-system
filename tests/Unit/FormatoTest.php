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
