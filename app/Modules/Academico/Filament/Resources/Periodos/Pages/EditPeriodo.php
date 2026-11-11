<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Pages;

use App\Modules\Academico\Actions\VerificarCrucesDelPeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Actions\AccionesDePeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use App\Modules\Academico\Filament\Resources\Periodos\Schemas\PeriodoForm;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Carbon\CarbonInterface;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class EditPeriodo extends EditRecord
{
    #[\Override]
    protected static string $resource = PeriodoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return Transaccion::ejecutar(fn (): Model => $this->actualizar($record, $data), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        } catch (UniqueConstraintViolationException $error) {
            // Another administrator took the same year and number after validation ran.
            throw RestriccionUnica::es($error, 'aca_periodos_anio_numero_unique')
                ? ValidationException::withMessages(['data.numero' => PeriodoForm::PERIODO_REPETIDO])
                : $error;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function actualizar(Model $record, array $data): Model
    {
        [$desde, $hasta] = [$record->getAttribute('fecha_inicio'), $record->getAttribute('fecha_fin')];
        $periodo = parent::handleRecordUpdate($record, $data);

        if ($desde instanceof CarbonInterface && $hasta instanceof CarbonInterface && [$periodo->getAttribute('fecha_inicio'), $periodo->getAttribute('fecha_fin')] != [$desde, $hasta]) {
            app(VerificarCrucesDelPeriodo::class)((int) $periodo->getKey(), $desde, $hasta);
        }

        return $periodo;
    }

    protected function getHeaderActions(): array
    {
        return AccionesDePeriodo::cambiosDeEstado();
    }
}
