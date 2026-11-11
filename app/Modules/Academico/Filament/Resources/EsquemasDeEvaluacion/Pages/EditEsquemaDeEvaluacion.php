<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages;

use App\Modules\Academico\Actions\GuardarEsquemaDeEvaluacion;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\EsquemaDeEvaluacionResource;
use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Schemas\EsquemaDeEvaluacionForm;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditEsquemaDeEvaluacion extends EditRecord
{
    #[\Override]
    protected static string $resource = EsquemaDeEvaluacionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $esquema = $this->getRecord();

        return $esquema instanceof EsquemaEvaluacion ? EsquemaDeEvaluacionForm::desde($esquema) : $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(GuardarEsquemaDeEvaluacion::class)(EsquemaDeEvaluacionForm::datos($data), (int) $record->getKey());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
