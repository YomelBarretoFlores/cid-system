<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum AccionFinal: string
{
    case Ninguna = 'ninguna';
    case ConstanciaMatricula = 'constancia_matricula';
    case ConstanciaNotas = 'constancia_notas';
    case CertificadoNivel = 'certificado_nivel';
    case Traslado = 'traslado';
    case Retiro = 'retiro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ninguna => 'Ninguna (solo seguimiento)',
            self::ConstanciaMatricula => 'Emitir la constancia de matrícula',
            self::ConstanciaNotas => 'Emitir la constancia de notas',
            self::CertificadoNivel => 'Emitir el certificado de nivel',
            self::Traslado => 'Trasladar la matrícula',
            self::Retiro => 'Retirar la matrícula',
        };
    }

    public function pideMatricula(): bool
    {
        return in_array($this, [self::ConstanciaMatricula, self::Traslado, self::Retiro], true);
    }

    public function pideIdioma(): bool
    {
        return in_array($this, [self::ConstanciaNotas, self::CertificadoNivel], true);
    }

    // Both are issued per level: the grade record offers the levels with a passed cycle, the certificate the completed ones.
    public function pideNivel(): bool
    {
        return in_array($this, [self::ConstanciaNotas, self::CertificadoNivel], true);
    }

    public function pideMotivo(): bool
    {
        return in_array($this, [self::Traslado, self::Retiro], true);
    }

    public function emiteDocumento(): bool
    {
        return in_array($this, [self::ConstanciaMatricula, self::ConstanciaNotas, self::CertificadoNivel], true);
    }
}
