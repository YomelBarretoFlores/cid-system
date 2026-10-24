<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Docencia\Actions\GuardarDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Actions\CambiarEstado;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Filament\Resources\Docentes\Schemas\DocenteForm;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditDocente extends EditRecord
{
    protected static string $resource = DocenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CambiarEstado::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $docente = $this->getRecord();

        return $docente instanceof Docente ? DocenteForm::estado($docente) : $data;
    }

    /**
     * @param  Docente  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // The person is disabled on edit, so the form does not send it.
        $data['persona_id'] = $record->persona_id;

        try {
            return app(GuardarDocente::class)(DocenteForm::datos($data), $record->id);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return DocenteResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
