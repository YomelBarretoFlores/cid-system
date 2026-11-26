<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\TextosDeDocumento;

final readonly class TextosDeLosDocumentos
{
    public const string CIERRE = 'Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.';

    public const string ESCALA = 'Escala vigesimal de 0 a 20.';

    public const string CONSTANCIA_ACTA = 'Así consta en el Acta Final correspondiente, disponible para verificación en caso de estimarse necesario.';

    public const string CERTIFICADO = 'Por haber aprobado satisfactoriamente el nivel {nivel} del idioma {idioma} desarrollado en el Centro de Idiomas de la UNASAM; haciendo un total de {horas}.';

    // What documents issued before the texts were editable print: their detalle carries no "textos".
    public const array ORIGINALES = ['cierre' => self::CIERRE, 'escala' => self::ESCALA, 'constancia_acta' => self::CONSTANCIA_ACTA, 'certificado' => self::CERTIFICADO];

    public function __construct(
        public string $cierreConstanciaMatricula = self::CIERRE,
        public string $cierreConstanciaNotas = self::CIERRE,
        public string $escalaNotas = self::ESCALA,
        public string $programaAcreditacion = 'Programa de Enseñanza de Idioma Extranjero',
        public string $suficienciaAcreditacion = 'Examen de Suficiencia',
        public string $constanciaActaAcreditacion = self::CONSTANCIA_ACTA,
        public string $validezAcreditacion = 'Válido para obtener el Grado de {mencion}.',
        public string $textoCertificado = self::CERTIFICADO,
    ) {}

    public static function desde(TextosDeDocumento $textos): self
    {
        return new self(
            cierreConstanciaMatricula: $textos->cierre_constancia_matricula,
            cierreConstanciaNotas: $textos->cierre_constancia_notas,
            escalaNotas: $textos->escala_notas,
            programaAcreditacion: $textos->programa_acreditacion,
            suficienciaAcreditacion: $textos->suficiencia_acreditacion,
            constanciaActaAcreditacion: $textos->constancia_acta_acreditacion,
            validezAcreditacion: $textos->validez_acreditacion,
            textoCertificado: $textos->texto_certificado,
        );
    }

    /**
     * The texts a document of this type prints, frozen in its detalle at issue so a reprint never changes.
     *
     * @return array<string, string>
     */
    public function paraDocumento(TipoDocumento $tipo): array
    {
        return match ($tipo) {
            TipoDocumento::ConstanciaMatricula => ['cierre' => $this->cierreConstanciaMatricula],
            TipoDocumento::ConstanciaNotas => ['cierre' => $this->cierreConstanciaNotas, 'escala' => $this->escalaNotas],
            TipoDocumento::ConstanciaAcreditacion => ['escala' => $this->escalaNotas, 'constancia_acta' => $this->constanciaActaAcreditacion],
            TipoDocumento::CertificadoNivel => ['certificado' => $this->textoCertificado],
        };
    }
}
