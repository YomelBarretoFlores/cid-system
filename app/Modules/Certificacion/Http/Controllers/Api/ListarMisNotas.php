<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers\Api;

use App\Modules\Certificacion\Actions\ListarNotasDeEstudiante;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListarMisNotas
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarNotasDeEstudiante $listarNotas,
    ) {}

    /**
     * The student's final grades by language, from closed grade sheets only.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);
        $porIdioma = ($this->listarNotas)($ficha->personaId);

        return response()->json(['data' => array_map(fn (string $idioma, array $notas): array => [
            'idioma' => $idioma,
            'ciclos' => array_map(fn (NotaDelHistorial $nota): array => [
                'ciclo' => $nota->cicloNumero,
                'nombre' => $nota->cicloNombre,
                'nivel' => $nota->nivel->etiqueta(),
                'periodo' => $nota->periodo,
                'horas_lectivas' => $nota->horasLectivas,
                'promedio' => $nota->promedio,
                'estado_final' => $nota->estadoFinal->value,
                'estado_final_etiqueta' => $nota->estadoFinal->etiqueta(),
            ], $notas),
        ], array_keys($porIdioma), $porIdioma)]);
    }
}
