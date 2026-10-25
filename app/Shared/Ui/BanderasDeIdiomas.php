<?php

declare(strict_types=1);

namespace App\Shared\Ui;

// Implemented by the Academico module, which stores the flag chosen for each language.
interface BanderasDeIdiomas
{
    public function para(string $idioma): BanderaDeIdioma;
}
