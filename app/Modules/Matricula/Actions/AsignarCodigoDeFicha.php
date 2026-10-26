<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Models\Ficha;
use Illuminate\Support\Str;

// The first download of a period's record fixes its code; later downloads, and two at the same time, reuse it.
final readonly class AsignarCodigoDeFicha
{
    public function __invoke(int $personaId, int $periodoId): string
    {
        Ficha::query()->insertOrIgnore([
            'codigo' => (string) Str::ulid(), 'persona_id' => $personaId, 'periodo_id' => $periodoId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return (string) Ficha::query()->where('persona_id', $personaId)->where('periodo_id', $periodoId)->value('codigo');
    }
}
