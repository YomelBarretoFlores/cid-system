<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Models\Documento;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListarDocumentosDeEstudiante
{
    /**
     * The documents issued to a student, newest first, voided ones included so the student sees why one stopped counting.
     *
     * @return Collection<int, Documento>
     */
    public function __invoke(int $personaId): Collection
    {
        return Documento::query()->where('persona_id', $personaId)->orderByDesc('emitido_en')->orderByDesc('id')->get();
    }
}
