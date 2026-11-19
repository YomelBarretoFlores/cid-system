<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Filament\Resources\Personas\Pages\Concerns\InformaDocumentoRepetido;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Models\Persona;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class CreatePersona extends CreateRecord
{
    use InformaDocumentoRepetido;

    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $aceptado = (bool) Arr::pull($data, 'acepta_tratamiento', false);

        return $this->guardarSinRepetirDocumento(function () use ($data, $aceptado): Model {
            $persona = new Persona($data);
            $persona->registrarConsentimiento($aceptado);
            $persona->save();

            return $persona;
        });
    }

    protected function getRedirectUrl(): string
    {
        return PersonaResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
