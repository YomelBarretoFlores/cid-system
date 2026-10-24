<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface CatalogoDePeriodos
{
    /**
     * @return array<int, string> period id => name, newest first
     */
    public function __invoke(): array;
}
