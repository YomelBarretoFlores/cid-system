<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Pages;

use App\Modules\Academico\Filament\Resources\Periodos\Actions\AccionesDePeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use App\Modules\Academico\Filament\Resources\Periodos\Schemas\PeriodoForm;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class EditPeriodo extends EditRecord
{
    protected static string $resource = PeriodoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return Transaccion::ejecutar(fn (): Model => parent::handleRecordUpdate($record, $data), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        } catch (UniqueConstraintViolationException $error) {
            // Another administrator took the same year and number after validation ran.
            throw RestriccionUnica::es($error, 'aca_periodos_anio_numero_unique')
                ? ValidationException::withMessages(['data.numero' => PeriodoForm::PERIODO_REPETIDO])
                : $error;
        }
    }

    protected function getHeaderActions(): array
    {
        return AccionesDePeriodo::cambiosDeEstado();
    }
}
