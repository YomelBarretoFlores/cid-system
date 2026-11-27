<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class ContrasenaDeOficina
{
    /**
     * @return list<mixed>
     */
    public static function reglas(): array
    {
        return ['required', 'string', 'max:255', 'confirmed', Password::defaults()];
    }

    public static function validar(string $contrasena): void
    {
        $validacion = Validator::make(['contrasena' => $contrasena], ['contrasena' => ['required', 'string', 'max:255', Password::defaults()]]);

        throw_if($validacion->fails(), ReglaDeNegocioIncumplida::class, 'La contraseña debe tener al menos 8 caracteres, con letras y números.');
    }
}
