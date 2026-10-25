<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Shared\Ui\Formato;

// What the public page may show (Ley 29733): no reasons, no contact data, and the document number partly hidden.
final readonly class VerificacionDeDocumento
{
    public function __construct(
        public string $codigo,
        public string $tipo,
        public string $emitidoEl,
        public string $estudiante,
        public string $documentoOculto,
        public string $idioma,
        public ?string $nivel,
        public ?int $horasLectivas,
        public ?string $notaFinal,
        public string $resumen,
        public bool $vigente,
        public ?string $anuladoEl,
    ) {}

    public static function desde(Documento $documento): self
    {
        return new self(
            codigo: $documento->codigo,
            tipo: $documento->tipo->etiqueta(),
            emitidoEl: Formato::fechaCorta($documento->emitido_en->setTimezone('America/Lima')),
            estudiante: $documento->estudiante_nombre,
            documentoOculto: self::ocultar($documento->estudiante_documento),
            idioma: $documento->idioma,
            nivel: $documento->nivel?->etiqueta(),
            horasLectivas: $documento->horas_lectivas,
            notaFinal: $documento->nota_final,
            resumen: self::resumen($documento),
            vigente: $documento->estaVigente(),
            anuladoEl: $documento->anulado_en === null ? null : Formato::fechaCorta($documento->anulado_en->setTimezone('America/Lima')),
        );
    }

    public static function ocultar(string $documento): string
    {
        if (preg_match('/^(.*?)(\S+)$/u', $documento, $partes) !== 1) {
            return '••••';
        }

        return $partes[1].str_repeat('•', max(0, mb_strlen($partes[2]) - 4)).mb_substr($partes[2], -4);
    }

    private static function resumen(Documento $documento): string
    {
        $detalle = $documento->detalle;

        return match ($documento->tipo) {
            TipoDocumento::ConstanciaMatricula => "Grupo {$detalle['grupo']} · ciclo {$detalle['ciclo_numero']} · {$detalle['periodo']}",
            TipoDocumento::ConstanciaNotas => self::resumenDeNotas($detalle['notas']),
            TipoDocumento::CertificadoNivel => count($detalle['ciclos']).' ciclos aprobados del nivel',
        };
    }

    /**
     * @param  list<array{ciclo_nombre: string, estado_final: string}>  $notas
     */
    private static function resumenDeNotas(array $notas): string
    {
        $aprobadas = array_values(array_filter($notas, fn (array $nota): bool => $nota['estado_final'] === EstadoFinal::Aprobado->value));
        $ultimo = $aprobadas === [] ? 'ninguno' : end($aprobadas)['ciclo_nombre'];

        return count($notas)." notas finales registradas · último ciclo aprobado: {$ultimo}";
    }
}
