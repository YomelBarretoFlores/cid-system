<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos\Pages;

use App\Modules\Certificacion\Filament\Resources\Documentos\Actions\AccionesDeDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use Filament\Resources\Pages\ListRecords;

final class ListDocumentos extends ListRecords
{
    #[\Override]
    protected static string $resource = DocumentoResource::class;

    #[\Override]
    protected ?string $subheading = 'Cada documento lleva un código único y un QR que se verifica en la página pública del CILN.';

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeDocumento::emitirCertificadosDelPeriodo(),
            AccionesDeDocumento::emitir(),
        ];
    }
}
