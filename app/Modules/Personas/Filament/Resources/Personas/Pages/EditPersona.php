<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Filament\Resources\Personas\Pages\Concerns\InformaDocumentoRepetido;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class EditPersona extends EditRecord
{
    use InformaDocumentoRepetido;

    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $aceptado = (bool) Arr::pull($data, 'acepta_tratamiento', false);

        return $this->guardarSinRepetirDocumento(function () use ($record, $data, $aceptado): Model {
            if ($record instanceof Persona) {
                $record->registrarConsentimiento($aceptado);
            }

            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
