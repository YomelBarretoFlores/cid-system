<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion;

use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\CreateEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\EditEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages\ListEsquemasDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Schemas\EsquemaDeEvaluacionForm;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Tables\EsquemasDeEvaluacionTable;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class EsquemaDeEvaluacionResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = EsquemaEvaluacion::class;

    protected static ?string $slug = 'sistemas-de-evaluacion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Sistemas de evaluación';

    protected static ?string $modelLabel = 'sistema de evaluación';

    protected static ?string $pluralModelLabel = 'sistemas de evaluación';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return EsquemaDeEvaluacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EsquemasDeEvaluacionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEsquemasDeEvaluacion::route('/'),
            'create' => CreateEsquemaDeEvaluacion::route('/create'),
            'edit' => EditEsquemaDeEvaluacion::route('/{record}/edit'),
        ];
    }
}
