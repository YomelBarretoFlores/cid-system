<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos;

use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ViewDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\Schemas\DocumentoInfolist;
use App\Modules\Certificacion\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Modules\Certificacion\Models\Documento;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class DocumentoResource extends Resource
{
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Documento::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Certificación';

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    protected static ?string $navigationLabel = 'Constancias y certificados';

    #[\Override]
    protected static ?string $modelLabel = 'documento emitido';

    #[\Override]
    protected static ?string $pluralModelLabel = 'constancias y certificados';

    #[\Override]
    protected static ?string $slug = 'documentos';

    #[\Override]
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
