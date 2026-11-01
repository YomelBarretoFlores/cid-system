<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use Filament\Resources\Pages\EditRecord;

final class EditGradoAcademico extends EditRecord
{
    protected static string $resource = GradoAcademicoResource::class;
}
