<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos\Pages;

use App\Modules\Certificacion\Filament\Resources\Documentos\Actions\AccionesDeDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewDocumento extends ViewRecord
{
    #[\Override]
    protected static string $resource = DocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeDocumento::descargarPdf(),
            AccionesDeDocumento::verificacionPublica(),
            AccionesDeDocumento::anular(),
        ];
    }
}
