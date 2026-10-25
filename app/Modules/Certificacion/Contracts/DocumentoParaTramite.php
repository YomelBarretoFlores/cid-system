<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\DocumentoEmitido;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Shared\Auth\Models\User;

interface DocumentoParaTramite
{
    /**
     * Issues the document a finished trámite asks for, with the same rules and permission as the panel.
     *
     * @param  array{matricula_id?: int, idioma?: string, nivel?: string}  $datos
     */
    public function __invoke(TipoDocumento $tipo, int $personaId, array $datos, User $emisor): DocumentoEmitido;
}
