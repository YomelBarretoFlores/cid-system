<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio;

use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages\CreateDenominacionDelAnio;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages\EditDenominacionDelAnio;
use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages\ListDenominacionesDelAnio;
use App\Modules\Certificacion\Models\DenominacionDelAnio;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class DenominacionDelAnioResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = DenominacionDelAnio::class;

    #[\Override]
    protected static ?string $slug = 'denominacion-del-anio';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Institución';

    #[\Override]
    protected static ?int $navigationSort = 9;

    #[\Override]
    protected static ?string $navigationLabel = 'Denominación del año';

    #[\Override]
    protected static ?string $modelLabel = 'denominación del año';

    #[\Override]
    protected static ?string $pluralModelLabel = 'denominaciones del año';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'texto';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                TextInput::make('anio')->label('Año')->integer()->minValue(2000)->maxValue(2100)->required()->unique(ignoreRecord: true)->columnSpan(1),
                TextInput::make('texto')->label('Denominación')->required()->maxLength(250)->columnSpan(3)
                    ->helperText('Sin comillas; los documentos la imprimen entre « » sobre el título.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('anio')->label('Año')->sortable(),
                TextColumn::make('texto')->label('Denominación')->wrap()->searchable(),
            ])
            ->defaultSort('anio', 'desc')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDenominacionesDelAnio::route('/'),
            'create' => CreateDenominacionDelAnio::route('/create'),
            'edit' => EditDenominacionDelAnio::route('/{record}/edit'),
        ];
    }
}
