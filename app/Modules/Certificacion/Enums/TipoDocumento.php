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

    public function vistaPdf(): string
    {
        return 'certificacion::pdf.'.$this->prefijoPdf();
    }

    public function archivoPdf(string $codigo): string
    {
        return "{$this->prefijoPdf()}-{$codigo}.pdf";
    }

    private function prefijoPdf(): string
    {
        return match ($this) {
            self::ConstanciaMatricula => 'constancia-de-matricula',
            self::ConstanciaNotas => 'constancia-de-notas',
            self::CertificadoNivel => 'certificado-de-nivel',
        };
    }

    public function grupoDeFirma(): GrupoDeFirma
    {
        return $this === self::CertificadoNivel ? GrupoDeFirma::Certificados : GrupoDeFirma::Constancias;
    }
}
