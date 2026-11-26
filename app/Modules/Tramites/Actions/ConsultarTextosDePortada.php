<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Models\TextosDePortada;

final readonly class ConsultarTextosDePortada
{
    public function __invoke(): TextosDeLaPortada
    {
        $textos = TextosDePortada::query()->find(TextosDePortada::FILA);

        return $textos === null ? TextosDeLaPortada::porDefecto() : TextosDeLaPortada::desde($textos);
    }
}
