<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface ProgramaPorDefecto
{
    // The name of the program marked as the default, printed for groups saved before programs existed.
    public function __invoke(): string;
}
