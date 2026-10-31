<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

interface MatriculasAprobadas
{
    /**
     * @return list<int> enrollments marked Aprobado in a closed acta
     */
    public function __invoke(): array;
}
