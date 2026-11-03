<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

function tareaDeRespaldo(string $comando): ?Event
{
    return collect(app(Schedule::class)->events())->first(fn (Event $evento): bool => str_ends_with((string) $evento->command, "'artisan' {$comando}"));
}

it('runs the nightly backup and its cleanup only when scheduled backups are turned on', function (bool $programados): void {
    config(['ciln.respaldos.programados' => $programados]);

    $limpieza = tareaDeRespaldo('backup:clean');
    $respaldo = tareaDeRespaldo('backup:run');

    expect($limpieza)->not->toBeNull()
        ->and($respaldo)->not->toBeNull()
        ->and([$limpieza->expression, $limpieza->timezone])->toBe(['0 1 * * *', 'America/Lima'])
        ->and([$respaldo->expression, $respaldo->timezone])->toBe(['30 1 * * *', 'America/Lima'])
        ->and($limpieza->filtersPass(app()))->toBe($programados)
        ->and($respaldo->filtersPass(app()))->toBe($programados);
})->with(['turned on' => true, 'turned off' => false]);

it('keeps scheduled backups off unless the server turns them on', function (): void {
    expect(config('ciln.respaldos.programados'))->toBeFalse();
});
