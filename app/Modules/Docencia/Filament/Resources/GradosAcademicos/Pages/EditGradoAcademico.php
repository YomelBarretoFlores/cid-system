<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\GradosAcademicos\Pages;

use App\Modules\Docencia\Filament\Resources\GradosAcademicos\GradoAcademicoResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditGradoAcademico extends EditRecord
{
    #[\Override]
    protected static string $resource = GradoAcademicoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordUpdate($record, $data), GradoAcademicoResource::TABLA, GradoAcademicoResource::REPETIDO);
    }
}
