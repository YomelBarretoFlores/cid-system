<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Schemas;

use App\Modules\Pagos\Filament\Schemas\CampoDeMonto;
use App\Modules\Pagos\Filament\Schemas\CamposDePago;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class PagoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('persona_id')
                    ->label('Persona')
                    ->helperText('Busca por número de documento o por apellidos y nombres.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                    ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                    ->live()
                    ->required()
                    ->columnSpanFull(),
                CamposDePago::concepto(),
                CamposDePago::nivel(),
                CamposDePago::programa(),
                CamposDePago::medioDePago(),
                CamposDePago::numeroOperacion(),
                CamposDePago::fechaPago(),
                CampoDeMonto::make(),
                CamposDePago::recibo()->required(),
            ]);
    }
}
