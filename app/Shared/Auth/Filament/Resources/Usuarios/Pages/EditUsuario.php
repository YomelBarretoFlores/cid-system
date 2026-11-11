<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios\Pages;

use App\Shared\Auth\Actions\GuardarUsuarioDelPersonal;
use App\Shared\Auth\Filament\Resources\Usuarios\FormularioDeUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditUsuario extends EditRecord
{
    #[\Override]
    protected static string $resource = UsuarioResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $usuario = $this->getRecord();

        return $usuario instanceof User ? FormularioDeUsuario::estado($usuario) : $data;
    }

    /**
     * @param  User  $record
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(GuardarUsuarioDelPersonal::class)(FormularioDeUsuario::datos($data, null), AccionDeNegocio::usuario(), $record->id);
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }
}
