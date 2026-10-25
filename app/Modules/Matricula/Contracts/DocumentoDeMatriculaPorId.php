<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use App\Modules\Matricula\Data\DocumentoDeMatricula;

interface DocumentoDeMatriculaPorId
{
    public function __invoke(int $matriculaId): ?DocumentoDeMatricula;
}
