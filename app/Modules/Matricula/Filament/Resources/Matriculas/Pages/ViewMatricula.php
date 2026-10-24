<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Pages;

use App\Modules\Matricula\Filament\Resources\Matriculas\Actions\AccionesDeMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewMatricula extends ViewRecord
{
    protected static string $resource = MatriculaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeMatricula::descargarFicha(),
            ...AccionesDeMatricula::movimientos(),
        ];
    }
}
