<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Filament\Resources\Personas\Pages\Concerns\InformaDocumentoRepetido;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreatePersona extends CreateRecord
{
    use InformaDocumentoRepetido;

    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->guardarSinRepetirDocumento(fn (): Model => parent::handleRecordCreation($data));
    }

    protected function getRedirectUrl(): string
    {
        return PersonaResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
