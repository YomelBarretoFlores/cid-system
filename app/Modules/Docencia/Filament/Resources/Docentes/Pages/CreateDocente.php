<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Docencia\Actions\DarAccesoAlDocente;
use App\Modules\Docencia\Actions\GuardarDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Filament\Resources\Docentes\Schemas\DocenteForm;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\FormularioDePersona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

// One screen registers the person when nobody has the document yet, the teacher and, if asked, the account.
final class CreateDocente extends CreateRecord
{
    #[\Override]
    protected static string $resource = DocenteResource::class;

    #[\Override]
    protected static bool $canCreateAnother = false;

    private ?string $usuario = null;

    // The person page opens this form with the person already chosen.
    protected function afterFill(): void
    {
        $persona = request()->query('persona');

        if (is_string($persona) && ctype_digit($persona)) {
            $this->data[FormularioDePersona::CAMPO] = [...($this->data[FormularioDePersona::CAMPO] ?? []), ...app(FormularioDePersona::class)->estadoDe((int) $persona)];
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = AccionDeNegocio::usuario();

        try {
            return Transaccion::ejecutar(fn (): Docente => $this->registrar($data, $actor), reintentar: false);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function registrar(array $data, User $actor): Docente
    {
        $docente = app(GuardarDocente::class)(DocenteForm::datos($data, app(FormularioDePersona::class)->persona($data, $actor)));

        if ((bool) ($data['crear_cuenta'] ?? false)) {
            $this->usuario = app(DarAccesoAlDocente::class)($docente->id, (string) $data['contrasena'], $actor)->usuario;
        }

        return $docente;
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()->success()->title($this->usuario === null ? 'Docente registrado' : "Docente registrado. Usuario: {$this->usuario}");
    }

    protected function getRedirectUrl(): string
    {
        return DocenteResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
