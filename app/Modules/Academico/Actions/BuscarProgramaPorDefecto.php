<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ProgramaPorDefecto;
use App\Modules\Academico\Models\Programa;

final readonly class BuscarProgramaPorDefecto implements ProgramaPorDefecto
{
    private const string CODIGO = 'JA';

    public function __invoke(): string
    {
        $nombre = Programa::query()->where('codigo', self::CODIGO)->value('nombre');

        return is_string($nombre) ? $nombre : 'Jóvenes y Adultos';
    }
}
