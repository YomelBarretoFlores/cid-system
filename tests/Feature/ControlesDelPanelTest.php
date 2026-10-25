<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

it('uses the panel dropdown instead of the browser select in table filters too', function (): void {
    Filament::setCurrentPanel('admin');

    expect(SelectFilter::make('estado')->isNative())->toBeFalse()
        ->and(TernaryFilter::make('activo')->isNative())->toBeFalse();
});
