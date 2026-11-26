<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\DocumentoEmitido;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Shared\Auth\Models\User;

interface DocumentoParaTramite
{
    /**
     * Issues the document a finished trámite asks for, with the same rules and permission as the panel; grade records, accreditation records and certificates are per level.
     *
     * @param  array{matricula_id?: int, idioma?: string, nivel?: string, mencion_completa?: string, grado_otorgado?: ?string}  $datos  mencion_completa: the degree mention printed on the accreditation record; grado_otorgado: the degree it grants, frozen with the request
     */
    public function __invoke(TipoDocumento $tipo, int $personaId, array $datos, User $emisor): DocumentoEmitido;
}
