<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas;

use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ViewMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Schemas\MatriculaInfolist;
use App\Modules\Matricula\Filament\Resources\Matriculas\Tables\MatriculasTable;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class MatriculaResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Matricula::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Matrícula';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'matrícula';

    protected static ?string $pluralModelLabel = 'matrículas';

    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return MatriculaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatriculasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatriculas::route('/'),
            'view' => ViewMatricula::route('/{record}'),
        ];
    }
}
