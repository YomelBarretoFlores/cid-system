<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Modalidades;

use App\Modules\Academico\Filament\Resources\Modalidades\Pages\CreateModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\Pages\EditModalidad;
use App\Modules\Academico\Filament\Resources\Modalidades\Pages\ListModalidades;
use App\Modules\Academico\Models\Modalidad;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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

final class ModalidadResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe una modalidad con ese nombre.';

    public const string TABLA = 'aca_modalidades';

    #[\Override]
    protected static ?string $model = Modalidad::class;

    #[\Override]
    protected static ?string $slug = 'modalidades';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Académico';

    #[\Override]
    protected static ?int $navigationSort = 14;

    #[\Override]
    protected static ?string $navigationLabel = 'Modalidades';

    #[\Override]
    protected static ?string $modelLabel = 'modalidad';

    #[\Override]
    protected static ?string $pluralModelLabel = 'modalidades';

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
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => self::REPETIDO]),
                TextInput::make('orden')->label('Orden en las listas')->integer()->minValue(0)->maxValue(999)->default(0)->required(),
                Textarea::make('descripcion')->label('Descripción en la página pública')->maxLength(300)->rows(2)->columnSpanFull(),
                TextEntry::make('codigo')->label('Código')->visibleOn('edit')->helperText('Se genera al crear y no cambia.'),
                Toggle::make('usa_aula')->label('Se dicta en aula')->default(true)->disabledOn('edit')->helperText('No cambia después de crear la modalidad.'),
                Toggle::make('usa_enlace')->label('Admite enlace de clase en línea')->default(false)->disabledOn('edit')->helperText('Sin aula, el enlace es obligatorio.'),
                Toggle::make('activo')->label('Disponible para grupos nuevos')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('codigo')->label('Código')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden')->label('Orden')->sortable(),
                IconColumn::make('usa_aula')->label('Aula')->boolean(),
                IconColumn::make('usa_enlace')->label('En línea')->boolean(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([TernaryFilter::make('activo')->label('Disponible')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModalidades::route('/'),
            'create' => CreateModalidad::route('/create'),
            'edit' => EditModalidad::route('/{record}/edit'),
        ];
    }
}
