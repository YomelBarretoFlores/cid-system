<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\GradosFut;

use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\CreateGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\EditGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\ListGradosFut;
use App\Modules\Tramites\Models\GradoFut;
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

final class GradoFutResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe un grado con ese nombre.';

    public const string TABLA = 'tra_grados_fut';

    #[\Override]
    protected static ?string $model = GradoFut::class;

    #[\Override]
    protected static ?string $slug = 'grados-del-fut';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Trámites';

    #[\Override]
    protected static ?int $navigationSort = 16;

    #[\Override]
    protected static ?string $navigationLabel = 'Grados del FUT';

    #[\Override]
    protected static ?string $modelLabel = 'grado del FUT';

    #[\Override]
    protected static ?string $pluralModelLabel = 'grados del FUT';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('nombre')
                    ->label('Grado (como se elige en el FUT)')
                    ->placeholder('Maestría')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => self::REPETIDO]),
                TextInput::make('titulo_otorgado')
                    ->label('Grado que otorga (como lo imprime la acreditación)')
                    ->placeholder('Maestro')
                    ->required()
                    ->maxLength(60),
                TextInput::make('orden')->label('Orden en las listas')->integer()->minValue(0)->maxValue(999)->default(0)->required(),
                TextEntry::make('codigo')->label('Código')->visibleOn('edit')->helperText('Se genera al crear y no cambia.'),
                Toggle::make('activo')->label('Disponible en el FUT')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Grado')->searchable()->sortable(),
                TextColumn::make('titulo_otorgado')->label('Grado que otorga'),
                TextColumn::make('codigo')->label('Código')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden')->label('Orden')->sortable(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([TernaryFilter::make('activo')->label('Disponible')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradosFut::route('/'),
            'create' => CreateGradoFut::route('/create'),
            'edit' => EditGradoFut::route('/{record}/edit'),
        ];
    }
}
