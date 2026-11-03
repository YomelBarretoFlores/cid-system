<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\CondicionLaboralResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCondicionLaboral extends CreateRecord
{
    protected static string $resource = CondicionLaboralResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordCreation($data), CondicionLaboralResource::TABLA, CondicionLaboralResource::REPETIDO);
    }

    protected function getRedirectUrl(): string
    {
        return CondicionLaboralResource::getUrl('index');
    }
}
