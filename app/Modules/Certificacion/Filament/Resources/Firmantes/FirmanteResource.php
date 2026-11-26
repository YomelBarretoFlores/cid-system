<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Firmantes;

use App\Modules\Certificacion\Filament\Resources\Firmantes\Pages\CreateFirmante;
use App\Modules\Certificacion\Filament\Resources\Firmantes\Pages\EditFirmante;
use App\Modules\Certificacion\Filament\Resources\Firmantes\Pages\ListFirmantes;
use App\Modules\Certificacion\Models\Firmante;
use App\Modules\Certificacion\Policies\FirmantePolicy;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class FirmanteResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Firmante::class;

    #[\Override]
    protected static ?string $slug = 'firmantes';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Institución';

    #[\Override]
    protected static ?int $navigationSort = 8;

    #[\Override]
    protected static ?string $navigationLabel = 'Firmantes';

    #[\Override]
    protected static ?string $modelLabel = 'firmante';

    #[\Override]
    protected static ?string $pluralModelLabel = 'firmantes';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('cargo')->label('Cargo')->required()->maxLength(80)->helperText('Como se imprime bajo la firma, por ejemplo: Director del CILN.'),
                TextInput::make('nombre')->label('Nombre con grado')->required()->maxLength(120)->helperText('Por ejemplo: Dr. Mario Huerta Ríos.'),
                Fieldset::make('Firma')
                    ->columns(3)
                    ->schema([
                        Toggle::make('firma_certificados')->label('Certificados')
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (! $value && ! $get('firma_constancias') && ! $get('firma_actas')) {
                                    $fail('Marca al menos un tipo de documento que firma.');
                                }
                            }),
                        Toggle::make('firma_constancias')->label('Constancias'),
                        Toggle::make('firma_actas')->label('Actas')
                            ->disabled(fn (): bool => ! FirmantePolicy::firmaActas(AccionDeNegocio::usuario()))
                            ->helperText(fn (): ?string => FirmantePolicy::firmaActas(AccionDeNegocio::usuario()) ? null : 'Solo quien gestiona las actas finales marca a quien las firma.'),
                    ]),
                TextInput::make('orden')->label('Orden en el documento')->integer()->minValue(1)->maxValue(9)->default(1)->required(),
                DatePicker::make('vigente_desde')->label('Firma desde')->required(),
                DatePicker::make('vigente_hasta')->label('Firma hasta')->afterOrEqual('vigente_desde')->helperText('Déjalo vacío mientras siga en el cargo.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cargo')->label('Cargo')->searchable(),
                TextColumn::make('nombre')->label('Nombre')->searchable(),
                IconColumn::make('firma_certificados')->label('Certificados')->boolean(),
                IconColumn::make('firma_constancias')->label('Constancias')->boolean(),
                IconColumn::make('firma_actas')->label('Actas')->boolean(),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('En el cargo'),
            ])
            ->defaultSort('orden')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFirmantes::route('/'),
            'create' => CreateFirmante::route('/create'),
            'edit' => EditFirmante::route('/{record}/edit'),
        ];
    }
}
