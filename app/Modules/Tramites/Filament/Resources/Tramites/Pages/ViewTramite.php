<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites\Pages;

use App\Modules\Tramites\Filament\Resources\Tramites\Actions\AccionesDeTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewTramite extends ViewRecord
{
    #[\Override]
    protected static string $resource = TramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeTramite::avanzar(),
            AccionesDeTramite::finalizar(),
            AccionesDeTramite::observar(),
            AccionesDeTramite::reanudar(),
            AccionesDeTramite::cerrar(),
        ];
    }
}
