<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages\CreateGradoAcademico;
use App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages\EditGradoAcademico;
use App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages\ListGradosAcademicos;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class GradoAcademicoResource extends Resource
{
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe un grado académico con ese nombre.';

    public const string TABLA = 'doc_grados_academicos';

    protected static ?string $model = GradoAcademico::class;

    protected static ?string $slug = 'grados-academicos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Grados académicos';

    protected static ?string $modelLabel = 'grado académico';

    protected static ?string $pluralModelLabel = 'grados académicos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return CatalogoEnElPanel::formulario($schema, self::REPETIDO);
    }

    public static function table(Table $table): Table
    {
        return CatalogoEnElPanel::tabla($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGradosAcademicos::route('/'),
            'create' => CreateGradoAcademico::route('/create'),
            'edit' => EditGradoAcademico::route('/{record}/edit'),
        ];
    }
}
