<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Controllers\Api;

use App\Modules\Docencia\Actions\ListarGruposDelDocente;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Http\Resources\GrupoDelDocenteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisGrupos
{
    public function __construct(
        private DocenteDelUsuario $docenteDelUsuario,
        private ListarGruposDelDocente $listarGrupos,
    ) {}

    /**
     * The teacher's current groups, latest period first, with their weekly schedule.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $docenteId = ($this->docenteDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);

        return GrupoDelDocenteResource::collection(($this->listarGrupos)($docenteId));
    }
}
