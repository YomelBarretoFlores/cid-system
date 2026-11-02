<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\EnlaceDeContrasena;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

// For the DSTI from the console: the first administrator of a new installation, or a way back in when nobody can manage users.
final readonly class CrearAdministradorDelPersonal
{
    public function __invoke(string $correo, string $nombre): User
    {
        $correo = mb_strtolower(trim($correo));
        throw_unless(filter_var($correo, FILTER_VALIDATE_EMAIL), ReglaDeNegocioIncumplida::class, 'El correo no es válido.');
        throw_if(trim($nombre) === '', ReglaDeNegocioIncumplida::class, 'Escribe el nombre de la persona.');

        $usuario = Transaccion::ejecutar(function () use ($correo, $nombre): User {
            $usuario = User::query()->where('email', $correo)->lockForUpdate()->first()
                ?? (new User)->forceFill(['email' => $correo, 'name' => trim($nombre), 'password' => Str::password(64)]);

            $usuario->forceFill(['desactivado_en' => null])->save();
            $usuario->givePermissionTo(DB::table('permissions')->where('guard_name', 'web')->pluck('name')->all());

            return $usuario;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Log::info('Se otorgó la administración completa a una cuenta desde la consola.', ['usuario_id' => $usuario->id]);
        EnlaceDeContrasena::enviar($usuario->email);

        return $usuario;
    }
}
