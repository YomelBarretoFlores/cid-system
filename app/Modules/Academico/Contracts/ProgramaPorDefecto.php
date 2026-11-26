<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface ProgramaPorDefecto
{
    // The name of the program groups belonged to before programs existed (Jóvenes y Adultos).
    public function __invoke(): string;
}
