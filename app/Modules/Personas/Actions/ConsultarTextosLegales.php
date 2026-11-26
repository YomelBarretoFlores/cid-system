<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Data\TextosLegalesVigentes;
use App\Modules\Personas\Models\TextosLegales;

final readonly class ConsultarTextosLegales
{
    // Until staff save them the row does not exist and the original wording applies.
    public function __invoke(): TextosLegalesVigentes
    {
        $textos = TextosLegales::query()->find(TextosLegales::FILA);

        return $textos === null ? new TextosLegalesVigentes : TextosLegalesVigentes::desde($textos);
    }
}
