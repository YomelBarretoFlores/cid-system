<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposDocumento;

use App\Modules\Personas\Actions\GuardarTipoDeDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\CreateTipoDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\EditTipoDocumento;
use App\Modules\Personas\Filament\Resources\TiposDocumento\Pages\ListTiposDocumento;
use App\Modules\Personas\Models\TipoDocumento;
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

final class TipoDocumentoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe un tipo de documento con ese nombre.';

    public const string TABLA = 'per_tipos_documento';

    #[\Override]
    protected static ?string $model = TipoDocumento::class;

    #[\Override]
    protected static ?string $slug = 'tipos-de-documento';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Personas';

    #[\Override]
    protected static ?int $navigationSort = 13;

    #[\Override]
    protected static ?string $navigationLabel = 'Tipos de documento';

    #[\Override]
    protected static ?string $modelLabel = 'tipo de documento';

    #[\Override]
    protected static ?string $pluralModelLabel = 'tipos de documento';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        $tope = GuardarTipoDeDocumento::LONGITUD_TOPE;

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
                TextEntry::make('codigo')->label('Código')->visibleOn('edit')->helperText('Se genera al crear y no cambia.'),
                Toggle::make('solo_digitos')->label('El número tiene solo dígitos')->helperText('Si no se marca, admite letras y dígitos, sin espacios ni guiones.')->default(false),
                TextInput::make('longitud_minima')->label('Longitud mínima')->integer()->minValue(1)->maxValue($tope)->required(),
                TextInput::make('longitud_maxima')->label('Longitud máxima')->integer()->minValue(1)->maxValue($tope)->gte('longitud_minima')->required()
                    ->helperText('La regla se aplica a los registros nuevos o editados; los números ya guardados no cambian.'),
                Toggle::make('activo')->label('Disponible en los formularios')->default(true),
                Toggle::make('por_defecto')->label('Preseleccionado al crear una cuenta en el portal')->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('codigo')->label('Código')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitud_minima')->label('Longitud')
                    ->formatStateUsing(fn (int $state, TipoDocumento $record): string => $state === $record->longitud_maxima ? (string) $state : "{$state} a {$record->longitud_maxima}"),
                IconColumn::make('solo_digitos')->label('Solo dígitos')->boolean(),
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
            'index' => ListTiposDocumento::route('/'),
            'create' => CreateTipoDocumento::route('/create'),
            'edit' => EditTipoDocumento::route('/{record}/edit'),
        ];
    }
}
