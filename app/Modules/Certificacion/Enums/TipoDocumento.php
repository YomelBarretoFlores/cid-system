<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

enum TipoDocumento: string
{
    case ConstanciaMatricula = 'constancia_matricula';
    case ConstanciaNotas = 'constancia_notas';
    case CertificadoNivel = 'certificado_nivel';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ConstanciaMatricula => 'Constancia de matrícula',
            self::ConstanciaNotas => 'Constancia de notas',
            self::CertificadoNivel => 'Certificado de culminación de nivel',
        };
    }
}
