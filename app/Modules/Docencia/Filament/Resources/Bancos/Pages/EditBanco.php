<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Bancos\Pages;

use App\Modules\Docencia\Filament\Resources\Bancos\BancoResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditBanco extends EditRecord
{
    #[\Override]
    protected static string $resource = BancoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordUpdate($record, $data), BancoResource::TABLA, BancoResource::REPETIDO);
    }
}
