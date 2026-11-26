<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\EnlaceDeContrasena;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

// Staff create the account of a record already on file after checking the identity document at the office;
// the emailed link to set the password is what proves the email belongs to that person.
final readonly class CrearCuentaDelPortal
{
    public function __invoke(int $personaId, User $personal): User
    {
        throw_unless($personal->can(PermisoPersona::Gestionar->value), AuthorizationException::class);

        $usuario = Transaccion::ejecutar(function () use ($personaId): User {
            $persona = Persona::query()->whereKey($personaId)->lockForUpdate()->firstOrFail();
            $correo = mb_strtolower(trim((string) $persona->correo));

            throw_if($persona->user_id !== null, ReglaDeNegocioIncumplida::class, 'Esta persona ya tiene una cuenta del portal.');
            throw_if($correo === '', ReglaDeNegocioIncumplida::class, 'Registra primero el correo personal de la persona.');
            throw_if(User::query()->where('email', $correo)->exists(), ReglaDeNegocioIncumplida::class, 'Ya existe una cuenta con ese correo.');

            $usuario = User::query()->create([
                'name' => "{$persona->nombres} {$persona->apellido_paterno}",
                'email' => $correo,
                'password' => Str::password(32),
            ]);
            $persona->user()->associate($usuario)->save();

            return $usuario;
        });

        EnlaceDeContrasena::enviar($usuario->email);

        return $usuario;
    }
}
