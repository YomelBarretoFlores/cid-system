<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;

final class ResultadosDelActa
{
    /**
     * @return array<int, array{promedio: ?string, estado_final: string, promedio_hasta?: ?string, estado_final_hasta?: ?string}> by enrollment id
     */
    public static function de(Acta $acta): array
    {
        return $acta->notas()->get()->mapWithKeys(fn (NotaFinal $nota): array => [$nota->matricula_id => self::resultado($nota)])->all();
    }

    /**
     * People whose final grade or state differs from the earlier results; every person of the acta when there are none to compare.
     *
     * @param  ?array<int, array{promedio: ?string, estado_final: string, promedio_hasta?: ?string, estado_final_hasta?: ?string}>  $previos
     * @return list<int>
     */
    public static function personasConCambios(Acta $acta, ?array $previos): array
    {
        $cambiadas = $acta->notas()->get()->filter(fn (NotaFinal $nota): bool => self::cambio($nota, $previos[$nota->matricula_id] ?? null));

        return array_values(array_unique($cambiadas->pluck('persona_id')->map(intval(...))->all()));
    }

    /**
     * @param  ?array{promedio: ?string, estado_final: string, promedio_hasta?: ?string, estado_final_hasta?: ?string}  $previo
     */
    private static function cambio(NotaFinal $nota, ?array $previo): bool
    {
        // Results stored before accelerated actas graded their second cycle apart have no second-cycle keys.
        return $previo === null || [
            'promedio' => $previo['promedio'], 'estado_final' => $previo['estado_final'],
            'promedio_hasta' => $previo['promedio_hasta'] ?? null, 'estado_final_hasta' => $previo['estado_final_hasta'] ?? null,
        ] !== self::resultado($nota);
    }

    /**
     * @return array{promedio: ?string, estado_final: string, promedio_hasta: ?string, estado_final_hasta: ?string}
     */
    private static function resultado(NotaFinal $nota): array
    {
        return ['promedio' => $nota->promedio, 'estado_final' => $nota->estado_final->value, 'promedio_hasta' => $nota->promedio_hasta, 'estado_final_hasta' => $nota->estado_final_hasta?->value];
    }
}
