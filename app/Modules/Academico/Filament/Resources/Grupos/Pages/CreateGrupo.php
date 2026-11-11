<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateGrupo extends CreateRecord
{
    #[\Override]
    protected static string $resource = GrupoResource::class;

    #[\Override]
    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(GuardarGrupo::class)(GrupoForm::datos($data));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return GrupoResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
