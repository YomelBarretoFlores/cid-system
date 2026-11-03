<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Schemas;

use Filament\Forms\Components\TextInput;

final class CampoDeMonto
{
    // numeric(12,2) would silently round 150.555, so more than two decimals is refused here.
    public static function make(): TextInput
    {
        return TextInput::make('monto')
            ->label('Monto')
            ->prefix('S/')
            ->numeric()
            ->step('0.01')
            ->minValue(0.01)
            ->maxValue(99999999.99)
            ->rule('decimal:0,2')
            ->validationMessages(['decimal' => 'El monto admite como máximo dos decimales.'])
            ->required();
    }
}
