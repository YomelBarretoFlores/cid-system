<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Pages;

use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use App\Modules\Academico\Filament\Resources\Periodos\Schemas\PeriodoForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class CreatePeriodo extends CreateRecord
{
    protected static string $resource = PeriodoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException $error) {
            // Another administrator created the same year and number after validation ran.
            throw str_contains($error->getMessage(), 'aca_periodos_anio_numero_unique')
                ? ValidationException::withMessages(['data.numero' => PeriodoForm::PERIODO_REPETIDO])
                : $error;
        }
    }

    protected function getRedirectUrl(): string
    {
        return PeriodoResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
