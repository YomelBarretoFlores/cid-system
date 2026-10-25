<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Controllers\Api;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Http\Resources\FichaDeEstudianteResource;
use Illuminate\Http\Request;

final readonly class MostrarPerfil
{
    public function __construct(private FichaDelUsuario $fichaDelUsuario) {}

    /**
     * The student's own record: name, identity document and contact data.
     */
    public function __invoke(Request $request): FichaDeEstudianteResource
    {
        return new FichaDeEstudianteResource(($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403));
    }
}
