<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Contracts;

interface TitulosDeGrados
{
    /**
     * @return array<string, string> grade name => degree it grants (Maestría => Maestro), active or not, so stored mentions keep their title
     */
    public function __invoke(): array;
}
