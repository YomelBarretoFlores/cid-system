<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Actions\AccionesDeGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditGrupo extends EditRecord
{
    protected static string $resource = GrupoResource::class;

    protected function getHeaderActions(): array
    {
        return AccionesDeGrupo::estados();
    }

    /**
     * @param  Grupo  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Period and cycle are disabled on edit, so the form does not send them.
        $data += ['periodo_id' => $record->periodo_id, 'ciclo_id' => $record->ciclo_id];

        try {
            return app(GuardarGrupo::class)(GrupoForm::datos($data), $record->id);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }
}
