<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Models\Ficha;
use Illuminate\Support\Str;
use RuntimeException;

// The first download of a period's record fixes its code; later downloads, and two at the same time, reuse it.
// The unique index never stores a repeated code: a new code that collides with another record is ignored, so another one is drawn.
final readonly class AsignarCodigoDeFicha
{
    private const int INTENTOS = 3;

    public function __invoke(int $personaId, int $periodoId): string
    {
        for ($intento = 1; $intento <= self::INTENTOS; $intento++) {
            Ficha::query()->insertOrIgnore([
                'codigo' => (string) Str::ulid(), 'persona_id' => $personaId, 'periodo_id' => $periodoId,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $codigo = Ficha::query()->where('persona_id', $personaId)->where('periodo_id', $periodoId)->value('codigo');

            if (is_string($codigo)) {
                return $codigo;
            }
        }

        throw new RuntimeException('Could not assign a unique enrollment record code.');
    }
}
