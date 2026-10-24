<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Builder;

final readonly class BuscarPersonas implements BusquedaDePersonas
{
    public function __invoke(string $termino, int $limite = 50): array
    {
        $termino = addcslashes(trim($termino), '\\%_');

        if ($termino === '') {
            return [];
        }

        return Persona::query()
            ->where(fn (Builder $query) => $query
                ->whereLike('numero_documento', mb_strtoupper($termino).'%', caseSensitive: true)
                ->orWhereRaw("concat_ws(' ', apellido_paterno, apellido_materno, nombres) ILIKE ?", ["%{$termino}%"]))
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->limit($limite)
            ->get()
            ->mapWithKeys(fn (Persona $persona): array => [$persona->id => $persona->etiqueta])
            ->all();
    }
}
