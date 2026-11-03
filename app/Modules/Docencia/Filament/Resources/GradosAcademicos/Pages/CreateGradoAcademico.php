<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateGradoAcademico extends CreateRecord
{
    protected static string $resource = GradoAcademicoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordCreation($data), GradoAcademicoResource::TABLA, GradoAcademicoResource::REPETIDO);
    }

    protected function getRedirectUrl(): string
    {
        return GradoAcademicoResource::getUrl('index');
    }
}
