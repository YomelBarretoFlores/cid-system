<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios\Pages;

use App\Shared\Auth\Actions\GuardarUsuarioDelPersonal;
use App\Shared\Auth\Filament\Resources\Usuarios\FormularioDeUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(GuardarUsuarioDelPersonal::class)(FormularioDeUsuario::datos($data, is_string($data['email'] ?? null) ? $data['email'] : null), AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Usuario creado. Se le envió el enlace para crear su contraseña.';
    }

    protected function getRedirectUrl(): string
    {
        return UsuarioResource::getUrl('index');
    }
}
