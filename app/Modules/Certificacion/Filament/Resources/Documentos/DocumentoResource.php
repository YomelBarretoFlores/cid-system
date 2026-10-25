<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos;

use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ViewDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\Schemas\DocumentoInfolist;
use App\Modules\Certificacion\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Modules\Certificacion\Models\Documento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Certificación';

    protected static ?string $navigationLabel = 'Constancias y certificados';

    protected static ?string $modelLabel = 'documento emitido';

    protected static ?string $pluralModelLabel = 'Constancias y certificados';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $slug = 'documentos';

    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return DocumentoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
            'view' => ViewDocumento::route('/{record}'),
        ];
    }
}
