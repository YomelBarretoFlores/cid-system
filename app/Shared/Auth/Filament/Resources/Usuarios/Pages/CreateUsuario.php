<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios\Pages;

use App\Shared\Auth\Actions\GuardarUsuarioDelPersonal;
use App\Shared\Auth\Filament\Resources\Usuarios\FormularioDeUsuario;
use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use App\Shared\Auth\Support\EnlaceDeContrasena;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $usuario = app(GuardarUsuarioDelPersonal::class)(FormularioDeUsuario::datos($data, is_string($data['email'] ?? null) ? $data['email'] : null), AccionDeNegocio::usuario());
            $this->correoDeLaCuenta = $usuario->email;

            return $usuario;
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::detener($error);
        }
    }

    private string $correoDeLaCuenta = '';

    private ?string $enlaceNoEnviado = null;

    // The account is already saved, so a link that cannot go out is a warning with the way to resend it, not a failed creation.
    protected function afterCreate(): void
    {
        try {
            EnlaceDeContrasena::enviar($this->correoDeLaCuenta);
        } catch (ReglaDeNegocioIncumplida $error) {
            $this->enlaceNoEnviado = $error->getMessage();
        }
    }

    protected function getCreatedNotification(): Notification
    {
        if ($this->enlaceNoEnviado === null) {
            return Notification::make()->success()->title('Usuario creado. Se le envió el enlace para crear su contraseña.');
        }

        return Notification::make()->warning()->persistent()
            ->title('Usuario creado, pero no se pudo enviar el enlace para crear su contraseña.')
            ->body($this->enlaceNoEnviado.' Usa «Reenviar enlace para crear contraseña» en la lista.');
    }

    protected function getRedirectUrl(): string
    {
        return UsuarioResource::getUrl('index');
    }
}
