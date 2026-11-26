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
    case Suficiencia = 'suficiencia';
    case Reclasificacion = 'reclasificacion';
    case Acreditacion = 'acreditacion';
    case Resolucion = 'resolucion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ninguna => 'Ninguna (solo seguimiento)',
            self::ConstanciaMatricula => 'Emitir la constancia de matrícula',
            self::ConstanciaNotas => 'Emitir la constancia de notas',
            self::CertificadoNivel => 'Emitir el certificado de nivel',
            self::Traslado => 'Trasladar la matrícula',
            self::Retiro => 'Retirar la matrícula',
            self::Suficiencia => 'Registrar la nota del examen de suficiencia',
            self::Reclasificacion => 'Asignar el ciclo de la reclasificación',
            self::Acreditacion => 'Emitir la constancia de acreditación',
            self::Resolucion => 'Resolver con un comentario del personal',
        };
    }

    /**
     * @return list<CampoFut> the fields this final action reads, so a type can never drop them or make them optional
     */
    public function camposRequeridos(): array
    {
        return match ($this) {
            self::ConstanciaMatricula => [CampoFut::Matricula],
            self::ConstanciaNotas, self::CertificadoNivel, self::Suficiencia => [CampoFut::Idioma, CampoFut::Nivel],
            self::Traslado, self::Retiro => [CampoFut::Matricula, CampoFut::Motivo],
            self::Reclasificacion => [CampoFut::Idioma],
            self::Acreditacion => [CampoFut::Idioma, CampoFut::Nivel, CampoFut::Mencion],
            self::Ninguna, self::Resolucion => [],
        };
    }

    /**
     * @return list<array{clave: string, obligatorio: bool}>
     */
    public function camposPorDefecto(): array
    {
        return array_map(fn (CampoFut $campo): array => ['clave' => $campo->value, 'obligatorio' => true], $this->camposRequeridos());
    }

    // Issued per level: the grade and accreditation records offer the levels with a passed cycle, the certificate the completed ones.
    public function validaIdiomaYNivel(): bool
    {
        return in_array($this, [self::ConstanciaNotas, self::CertificadoNivel, self::Acreditacion], true);
    }

    public function pideNivelesConNotas(): bool
    {
        return in_array($this, [self::ConstanciaNotas, self::Acreditacion], true);
    }

    public function emiteDocumento(): bool
    {
        return in_array($this, [self::ConstanciaMatricula, self::ConstanciaNotas, self::CertificadoNivel, self::Acreditacion], true);
    }
}
