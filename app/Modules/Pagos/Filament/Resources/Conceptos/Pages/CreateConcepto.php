<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Pages;

use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use App\Modules\Pagos\Filament\Resources\Conceptos\Schemas\ConceptoForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class CreateConcepto extends CreateRecord
{
    protected static string $resource = ConceptoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException $error) {
            // Another administrator created the same code after validation ran.
            throw str_contains($error->getMessage(), 'pag_conceptos_codigo_unique')
                ? ValidationException::withMessages(['data.codigo' => ConceptoForm::CODIGO_REPETIDO])
                : $error;
        }
    }

    protected function getRedirectUrl(): string
    {
        return ConceptoResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
