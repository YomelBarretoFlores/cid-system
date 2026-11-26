<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\FilasDeLaConstancia;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class EmitirConstanciaDeNotas
{
    public function __construct(private EmisionDeDocumento $emision, private FilasDeLaConstancia $filas) {}

    // One level per record, as CILN charges it, with the last passed result of each cycle (a graded one wins over a placement).
    public function __invoke(int $personaId, string $idioma, NivelCurricular $nivel, User $emisor): Documento
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $historial = HistorialDeNotas::de($personaId, $idioma);
        $aprobadas = [];

        foreach ($historial as $nota) {
            if ($nota->nivel === $nivel && $nota->estadoFinal === EstadoFinal::Aprobado) {
                $aprobadas[$nota->cicloNumero] = $nota;
            }
        }

        if ($aprobadas === []) {
            throw new ReglaDeNegocioIncumplida("La persona no tiene ciclos aprobados de {$idioma} en el nivel {$nivel->etiqueta()}.");
        }

        ksort($aprobadas);
        $notas = array_values($aprobadas);

        return $this->emision->emitir(TipoDocumento::ConstanciaNotas, $personaId, [
            'idioma' => $idioma,
            'nivel' => $nivel->value,
            'detalle' => [
                'version' => 2,
                ...$this->filas->datosDelEstudiante($personaId, $idioma, $historial),
                'notas' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), $notas),
                'filas' => FilasDeLaConstancia::filas($notas),
                'programa_curricular' => $this->filas->programaCurricular($idioma),
            ],
        ], $emisor);
    }
}
