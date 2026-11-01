<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateGradoAcademico extends CreateRecord
{
    protected static string $resource = GradoAcademicoResource::class;

    protected function getRedirectUrl(): string
    {
        return GradoAcademicoResource::getUrl('index');
    }
}
