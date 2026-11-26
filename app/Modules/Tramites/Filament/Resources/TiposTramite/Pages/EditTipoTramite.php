<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Pages;

use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\TiposTramite\TipoTramiteResource;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditTipoTramite extends EditRecord
{
    #[\Override]
    protected static string $resource = TipoTramiteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tipo = $this->getRecord();

        return $tipo instanceof TipoTramite ? TipoTramiteForm::estado($tipo->load(['etapas', 'programas'])) : $data;
    }

    /**
     * @param  TipoTramite  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos($data), AccionDeNegocio::usuario(), $record->id);
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
