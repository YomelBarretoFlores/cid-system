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
