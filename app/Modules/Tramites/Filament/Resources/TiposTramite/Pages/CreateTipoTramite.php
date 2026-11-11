<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Pages;

use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\TiposTramite\TipoTramiteResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateTipoTramite extends CreateRecord
{
    #[\Override]
    protected static string $resource = TipoTramiteResource::class;

    #[\Override]
    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos($data), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            throw new Halt($error->getMessage(), $error->getCode(), $error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return TipoTramiteResource::getUrl('index');
    }
}
