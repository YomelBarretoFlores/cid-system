<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ProgramaPorDefecto;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class BuscarProgramaPorDefecto implements ProgramaPorDefecto
{
    public const string SIN_POR_DEFECTO = 'El CILN aún no definió el programa por defecto en «Programas».';

    public function __invoke(): string
    {
        $nombre = Programa::query()->where('por_defecto', true)->value('nombre');

        return is_string($nombre) ? $nombre : throw new ReglaDeNegocioIncumplida(self::SIN_POR_DEFECTO);
    }
}
