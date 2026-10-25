<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Data\DocumentoDeMatricula;
use App\Modules\Matricula\Models\Matricula;

final readonly class BuscarDocumentoDeMatricula implements DocumentoDeMatriculaPorId
{
    public function __construct(private ComponerDocumentoDeMatricula $componer) {}

    public function __invoke(int $matriculaId): ?DocumentoDeMatricula
    {
        $matricula = Matricula::query()->find($matriculaId);

        return $matricula === null ? null : ($this->componer)($matricula);
    }
}
