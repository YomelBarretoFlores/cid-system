<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListGradosAcademicos extends ListRecords
{
    protected static string $resource = GradoAcademicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo grado académico'),
        ];
    }
}
