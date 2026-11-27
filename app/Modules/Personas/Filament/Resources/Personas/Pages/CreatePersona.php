<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Actions\RegistrarPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\Concerns\InformaDocumentoRepetido;
use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreatePersona extends CreateRecord
{
    use InformaDocumentoRepetido;

    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->guardarSinRepetirDocumento(fn (): Model => app(RegistrarPersona::class)(PersonaForm::datos($data), AccionDeNegocio::usuario()));
    }

    protected function getRedirectUrl(): string
    {
        return PersonaResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
