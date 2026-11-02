<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Data\DatosDeUsuarioDelPersonal;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\EnlaceDeContrasena;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

final readonly class GuardarUsuarioDelPersonal
{
    public const string CORREO_REPETIDO = 'Ya existe una cuenta con ese correo.';

    public function __invoke(DatosDeUsuarioDelPersonal $datos, User $actor, ?int $usuarioId = null): User
    {
        try {
            $usuario = Transaccion::ejecutar(fn (): User => $this->guardar($datos, $actor, $usuarioId));
        } catch (UniqueConstraintViolationException) {
            throw new ReglaDeNegocioIncumplida(self::CORREO_REPETIDO);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($usuarioId === null) {
            EnlaceDeContrasena::enviar($usuario->email);
        }

        return $usuario;
    }

    private function guardar(DatosDeUsuarioDelPersonal $datos, User $actor, ?int $usuarioId): User
    {
        GestoresDeUsuarios::bloquear();
        GestoresDeUsuarios::autorizar($actor);

        $usuario = $usuarioId === null ? $this->nueva($datos) : User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();
        $usuario->forceFill(['name' => trim($datos->nombre)])->save();
        $usuario->syncRoles($datos->roles);
        $usuario->syncPermissions([...array_diff($datos->permisos, [User::ADMIN_PANEL_PERMISSION]), User::ADMIN_PANEL_PERMISSION]);

        if ($usuario->id === $actor->id) {
            GestoresDeUsuarios::asegurarQueLoConserva($actor);
        }

        GestoresDeUsuarios::asegurarQueQuedeUno();

        return $usuario;
    }

    // Nobody ever knows this random password; the person sets their own through the emailed link.
    private function nueva(DatosDeUsuarioDelPersonal $datos): User
    {
        throw_if($datos->correo === null || trim($datos->correo) === '', ReglaDeNegocioIncumplida::class, 'Escribe el correo de la cuenta.');

        return (new User)->forceFill(['email' => $datos->correo, 'password' => Str::password(64)]);
    }
}
