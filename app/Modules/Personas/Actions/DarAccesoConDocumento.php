<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\AccesoConDocumento;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\CuentaSoloDelPortal;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Str;

// Staff give a teacher the identity document as username and a password at the office, with or without an email.
// A record that already has an account keeps it and only gets the username and the new password.
final readonly class DarAccesoConDocumento implements AccesoConDocumento
{
    public function __invoke(int $personaId, string $contrasena): CuentaDelPortal
    {
        return Transaccion::ejecutar(function () use ($personaId, $contrasena): CuentaDelPortal {
            $persona = Persona::query()->whereKey($personaId)->lockForUpdate()->firstOrFail();
            $usuario = (string) User::normalizarUsuario($persona->numero_documento);
            $cuenta = $persona->user_id === null ? null : CuentaSoloDelPortal::bloquear($persona->user_id);

            throw_if(
                User::query()->where('usuario', $usuario)->when($cuenta, fn ($consulta) => $consulta->whereKeyNot($cuenta?->id))->exists(),
                ReglaDeNegocioIncumplida::class,
                "Ya existe otra cuenta con el usuario {$usuario}.",
            );

            $cuenta ??= $this->nuevaCuenta($persona);
            $cuenta->forceFill(['usuario' => $usuario, 'password' => $contrasena, 'remember_token' => Str::random(60)])->save();
            // An account that already existed may have app tokens issued with its previous password.
            $cuenta->tokens()->delete();
            $persona->user()->associate($cuenta)->save();

            return ObtenerCuentasDePersonas::cuenta($persona->id, $cuenta);
        });
    }

    private function nuevaCuenta(Persona $persona): User
    {
        $correo = User::normalizarCorreo($persona->correo);
        $libre = $correo !== null && ! User::query()->where('email', $correo)->exists();

        return new User([
            'name' => "{$persona->nombres} {$persona->apellido_paterno}",
            'email' => $libre ? $correo : null,
        ]);
    }
}
