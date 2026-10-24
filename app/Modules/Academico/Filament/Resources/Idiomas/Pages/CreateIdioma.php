<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Pages;

use App\Modules\Academico\Filament\Resources\Idiomas\IdiomaResource;
use App\Modules\Academico\Filament\Resources\Idiomas\Schemas\IdiomaForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class CreateIdioma extends CreateRecord
{
    protected static string $resource = IdiomaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // The savepoint keeps the connection usable after a unique violation inside an outer transaction.
            return DB::transaction(fn (): Model => parent::handleRecordCreation($data));
        } catch (UniqueConstraintViolationException $error) {
            throw IdiomaForm::errorDeDuplicado($error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return IdiomaResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
