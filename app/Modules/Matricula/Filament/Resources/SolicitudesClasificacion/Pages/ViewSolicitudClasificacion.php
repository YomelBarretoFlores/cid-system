<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages;

use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Actions\AccionesDeSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\Concerns\ConsultaElCatalogoDeIdiomas;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\SolicitudClasificacionResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewSolicitudClasificacion extends ViewRecord
{
    use ConsultaElCatalogoDeIdiomas;

    protected static string $resource = SolicitudClasificacionResource::class;

    protected function getHeaderActions(): array
    {
        return [AccionesDeSolicitudClasificacion::registrarResultado(), AccionesDeSolicitudClasificacion::corregirResultado()];
    }
}
