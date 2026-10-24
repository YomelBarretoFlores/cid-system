<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\FichaDelUsuario;

final readonly class BuscarMatriculaDelEstudiante
{
    public function __construct(private FichaDelUsuario $fichaDelUsuario) {}

    // Another student's enrollment answers 404 like a missing one, so its existence is not revealed.
    public function __invoke(string $ulid, int $userId): Matricula
    {
        $personaId = (($this->fichaDelUsuario)($userId) ?? abort(403))->personaId;

        return Matricula::query()->where(['ulid' => $ulid, 'persona_id' => $personaId])->firstOrFail();
    }
}
