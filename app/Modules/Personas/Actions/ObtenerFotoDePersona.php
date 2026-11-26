<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\FotoDePersona;
use App\Modules\Personas\Models\Persona;
use Illuminate\Support\Facades\Storage;

final readonly class ObtenerFotoDePersona implements FotoDePersona
{
    private const array TIPOS = ['jpg' => 'image/jpeg', 'png' => 'image/png'];

    public function __invoke(int $personaId): ?string
    {
        $ruta = Persona::query()->whereKey($personaId)->value('foto');
        $tipo = is_string($ruta) ? self::TIPOS[pathinfo($ruta, PATHINFO_EXTENSION)] ?? null : null;
        $disco = Storage::disk(CambiarFotoDePerfil::DISCO);

        if ($tipo === null || ! $disco->exists((string) $ruta)) {
            return null;
        }

        return "data:{$tipo};base64,".base64_encode((string) $disco->get((string) $ruta));
    }
}
