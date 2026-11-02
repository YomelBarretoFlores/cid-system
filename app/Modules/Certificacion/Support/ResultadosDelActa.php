<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;

final class ResultadosDelActa
{
    /**
     * @return array<int, array{promedio: ?string, estado_final: string}> by enrollment id
     */
    public static function de(Acta $acta): array
    {
        return $acta->notas()->get()
            ->mapWithKeys(fn (NotaFinal $nota): array => [$nota->matricula_id => ['promedio' => $nota->promedio, 'estado_final' => $nota->estado_final->value]])
            ->all();
    }

    /**
     * People whose final grade or state differs from the earlier results; every person of the acta when there are none to compare.
     *
     * @param  ?array<int, array{promedio: ?string, estado_final: string}>  $previos
     * @return list<int>
     */
    public static function personasConCambios(Acta $acta, ?array $previos): array
    {
        $cambiadas = $acta->notas()->get()->filter(fn (NotaFinal $nota): bool => self::cambio($nota, $previos[$nota->matricula_id] ?? null));

        return array_values(array_unique($cambiadas->pluck('persona_id')->map(intval(...))->all()));
    }

    /**
     * @param  ?array{promedio: ?string, estado_final: string}  $previo
     */
    private static function cambio(NotaFinal $nota, ?array $previo): bool
    {
        return $previo === null || $previo['promedio'] !== $nota->promedio || $previo['estado_final'] !== $nota->estado_final->value;
    }
}
