<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages;

use App\Modules\Academico\Actions\GuardarEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\EsquemaDeEvaluacionResource;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Schemas\EsquemaDeEvaluacionForm;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateEsquemaDeEvaluacion extends CreateRecord
{
    protected static string $resource = EsquemaDeEvaluacionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(GuardarEsquemaDeEvaluacion::class)(EsquemaDeEvaluacionForm::datos($data));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    protected function getRedirectUrl(): string
    {
        return EsquemaDeEvaluacionResource::getUrl('index');
    }
}
