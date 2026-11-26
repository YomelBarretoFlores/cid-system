<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\TextosDeLosDocumentos;

// The editable sentences of the constancias, the accreditation and the certificate in force today.
interface TextosDeDocumentos
{
    public function __invoke(): TextosDeLosDocumentos;
}
