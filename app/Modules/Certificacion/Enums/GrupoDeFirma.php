<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

// Which documents a signer signs; the value is the signer's column.
enum GrupoDeFirma: string
{
    case Certificados = 'firma_certificados';
    case Constancias = 'firma_constancias';
    case Actas = 'firma_actas';
}
