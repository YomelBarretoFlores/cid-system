<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas\Pages;

use App\Modules\Academico\Filament\Resources\Programas\ProgramaResource;
use App\Modules\Academico\Filament\Resources\Programas\Schemas\ProgramaForm;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

final class EditPrograma extends EditRecord
{
    #[\Override]
    protected static string $resource = ProgramaResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return Transaccion::ejecutar(fn (): Model => parent::handleRecordUpdate($record, $data), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        } catch (UniqueConstraintViolationException $error) {
            throw ProgramaForm::errorDeDuplicado($error);
        }
    }
}
