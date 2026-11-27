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
        return [...self::reglasDeLaContrasena(), 'confirmed'];
    }

    public static function validar(string $contrasena): void
    {
        $validacion = Validator::make(['contrasena' => $contrasena], ['contrasena' => self::reglasDeLaContrasena()]);

        throw_if($validacion->fails(), ReglaDeNegocioIncumplida::class, 'La contraseña debe tener al menos 8 caracteres, con letras y números.');
    }

    /**
     * @return list<mixed>
     */
    private static function reglasDeLaContrasena(): array
    {
        return ['required', 'string', 'max:255', Password::defaults()];
    }
}
