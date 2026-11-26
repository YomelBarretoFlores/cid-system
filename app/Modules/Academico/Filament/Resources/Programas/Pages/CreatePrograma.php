<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas\Pages;

use App\Modules\Academico\Filament\Resources\Programas\ProgramaResource;
use App\Modules\Academico\Filament\Resources\Programas\Schemas\ProgramaForm;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreatePrograma extends CreateRecord
{
    #[\Override]
    protected static string $resource = ProgramaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // The savepoint keeps the connection usable after a unique violation inside an outer transaction.
            return Transaccion::ejecutar(fn (): Model => parent::handleRecordCreation($data), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        } catch (UniqueConstraintViolationException $error) {
            throw ProgramaForm::errorDeDuplicado($error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return ProgramaResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
