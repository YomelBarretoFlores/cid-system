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
