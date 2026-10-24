<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface CatalogoDeGrupos
{
    /**
     * @return array<int, string> group id => "code · period", newest period first
     */
    public function __invoke(): array;
}
