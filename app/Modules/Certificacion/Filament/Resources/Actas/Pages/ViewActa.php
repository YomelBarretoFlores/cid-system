<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Pages;

use App\Modules\Certificacion\Filament\Resources\Actas\ActaResource;
use App\Modules\Certificacion\Filament\Resources\Actas\Actions\AccionesDeActa;
use Filament\Resources\Pages\ViewRecord;

final class ViewActa extends ViewRecord
{
    protected static string $resource = ActaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeActa::descargarPdf(),
            AccionesDeActa::emitirConstancias(),
            AccionesDeActa::autorizarRectificacion(),
        ];
    }
}
