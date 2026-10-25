<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers\Api;

use App\Modules\Certificacion\Actions\ListarNotasDeEstudiante;
use App\Modules\Certificacion\Http\Resources\NotasPorIdiomaResource;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisNotas
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarNotasDeEstudiante $listarNotas,
    ) {}

    /**
     * The student's final grades by language, from closed grade sheets only.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);
        $porIdioma = ($this->listarNotas)($ficha->personaId);

        return NotasPorIdiomaResource::collection(array_map(fn (string $idioma, array $notas): array => ['idioma' => $idioma, 'notas' => $notas], array_keys($porIdioma), $porIdioma));
    }
}
