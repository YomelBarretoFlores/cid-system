<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposVinculo;

use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\CreateTipoVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\EditTipoVinculo;
use App\Modules\Personas\Filament\Resources\TiposVinculo\Pages\ListTiposVinculo;
use App\Modules\Personas\Models\TipoVinculo;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

final class TipoVinculoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe un tipo de vínculo con ese nombre.';

    public const string TABLA = 'per_tipos_vinculo';

    #[\Override]
    protected static ?string $model = TipoVinculo::class;

    #[\Override]
    protected static ?string $slug = 'tipos-de-vinculo';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Personas';

    #[\Override]
    protected static ?int $navigationSort = 12;

    #[\Override]
    protected static ?string $navigationLabel = 'Tipos de vínculo';

    #[\Override]
    protected static ?string $modelLabel = 'tipo de vínculo';

    #[\Override]
    protected static ?string $pluralModelLabel = 'tipos de vínculo';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => self::REPETIDO]),
                TextInput::make('orden')->label('Orden en las listas')->integer()->minValue(0)->maxValue(999)->default(0)->required(),
                TextEntry::make('codigo')->label('Código')->visibleOn('edit')->helperText('Se genera al crear y no cambia.'),
                Toggle::make('activo')->label('Disponible en los formularios y para tarifas nuevas')->default(true),
                Toggle::make('por_defecto')->label('Vínculo de quien crea su cuenta en el portal')->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('codigo')->label('Código')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden')->label('Orden')->sortable(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
                IconColumn::make('por_defecto')->label('Por defecto')->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([TernaryFilter::make('activo')->label('Disponible')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTiposVinculo::route('/'),
            'create' => CreateTipoVinculo::route('/create'),
            'edit' => EditTipoVinculo::route('/{record}/edit'),
        ];
    }
}
