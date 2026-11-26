<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\TextosDeDocumentos;
use App\Modules\Certificacion\Data\TextosDeLosDocumentos;
use App\Modules\Certificacion\Models\TextosDeDocumento;

final readonly class ConsultarTextosDeDocumentos implements TextosDeDocumentos
{
    // A missing row (a test truncation) falls back to the defaults the migration inserts.
    public function __invoke(): TextosDeLosDocumentos
    {
        $textos = TextosDeDocumento::query()->find(TextosDeDocumento::FILA);

        return $textos === null ? new TextosDeLosDocumentos : TextosDeLosDocumentos::desde($textos);
    }
}
