<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// The student only edits how to reach them; names, document, birth date and email stay with the CILN.
final readonly class ActualizarDatosDeContacto
{
    public const int DIRECCION_MAXIMA = 200;

    public function __invoke(int $personaId, ?string $celular, ?string $direccion): void
    {
        $celular = filled($celular) ? Persona::normalizarCelular(trim($celular)) : null;
        $direccion = filled($direccion) ? trim($direccion) : null;

        if ($celular !== null && preg_match(Persona::FORMATO_CELULAR, $celular) !== 1) {
            throw new ReglaDeNegocioIncumplida('Escribe un celular válido, solo con números.');
        }

        if ($direccion !== null && mb_strlen($direccion) > self::DIRECCION_MAXIMA) {
            throw new ReglaDeNegocioIncumplida('Tu dirección admite como máximo '.self::DIRECCION_MAXIMA.' caracteres.');
        }

        Transaccion::ejecutar(fn (): bool => Persona::query()->whereKey($personaId)->lockForUpdate()->firstOrFail()
            ->update(['celular' => $celular, 'direccion' => $direccion]));
    }
}
