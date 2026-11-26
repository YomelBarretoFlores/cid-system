<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Bancos\Pages;

use App\Modules\Docencia\Filament\Resources\Bancos\BancoResource;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateBanco extends CreateRecord
{
    #[\Override]
    protected static string $resource = BancoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CatalogoEnElPanel::guardar(fn (): Model => parent::handleRecordCreation($data), BancoResource::TABLA, BancoResource::REPETIDO);
    }

    protected function getRedirectUrl(): string
    {
        return BancoResource::getUrl('index');
    }
}
