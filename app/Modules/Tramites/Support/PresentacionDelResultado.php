<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Models\Tramite;

// The outcome staff recorded when finishing, as the student and staff read it.
final class PresentacionDelResultado
{
    public static function texto(Tramite $tramite): ?string
    {
        $resultado = $tramite->resultado ?? [];

        return match (true) {
            isset($resultado['nota']) => "Nota del examen: {$resultado['nota']} · ".(($resultado['aprobado'] ?? false) ? 'Aprobado' : 'Desaprobado')." (nota mínima {$resultado['nota_minima']})",
            isset($resultado['ciclo']) => "Ciclo asignado: {$resultado['ciclo']}",
            isset($resultado['comentario']) => "Respuesta del CILN: {$resultado['comentario']}",
            default => null,
        };
    }
}
