<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final readonly class QuitarFotoDePerfil
{
    public function __invoke(int $personaId): void
    {
        $anterior = Transaccion::ejecutar(function () use ($personaId): ?string {
            $persona = Persona::query()->whereKey($personaId)->lockForUpdate()->firstOrFail();
            $anterior = $persona->foto;

            if ($anterior !== null) {
                $persona->update(['foto' => null]);
            }

            return $anterior;
        });

        DB::afterCommit(fn (): bool => $anterior === null || Storage::disk(CambiarFotoDePerfil::DISCO)->delete($anterior));
    }
}
