<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Pages;

use App\Modules\Academico\Filament\Resources\Aulas\AulaResource;
use App\Modules\Academico\Filament\Resources\Aulas\Schemas\AulaForm;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class CreateAula extends CreateRecord
{
    protected static string $resource = AulaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // The savepoint keeps the connection usable after a unique violation inside an outer transaction.
            return Transaccion::ejecutar(fn (): Model => parent::handleRecordCreation($data));
        } catch (UniqueConstraintViolationException $error) {
            // Another coordinator created the same code after validation ran.
            throw RestriccionUnica::es($error, 'aca_aulas_codigo_unique')
                ? ValidationException::withMessages(['data.codigo' => AulaForm::CODIGO_REPETIDO])
                : $error;
        }
    }

    protected function getRedirectUrl(): string
    {
        return AulaResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
