<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Pages;

use App\Modules\Academico\Filament\Resources\Periodos\Actions\AccionesDePeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use Filament\Resources\Pages\EditRecord;

final class EditPeriodo extends EditRecord
{
    protected static string $resource = PeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return AccionesDePeriodo::cambiosDeEstado();
    }
}
