<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\CondicionLaboralResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditCondicionLaboral extends EditRecord
{
    protected static string $resource = CondicionLaboralResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordUpdate($record, $data), CondicionLaboralResource::TABLA, CondicionLaboralResource::REPETIDO);
    }
}
