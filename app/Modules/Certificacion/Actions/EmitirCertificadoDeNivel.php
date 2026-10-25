<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class EmitirCertificadoDeNivel
{
    public function __construct(private EmisionDeDocumento $emision) {}

    public function __invoke(int $personaId, string $idioma, NivelCurricular $nivel, User $emisor): Documento
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $ciclos = HistorialDeNotas::nivelAprobado(HistorialDeNotas::de($personaId, $idioma), $nivel)
            ?? throw new ReglaDeNegocioIncumplida("La persona aún no aprueba los cuatro ciclos del nivel {$nivel->etiqueta()} de {$idioma} en actas cerradas.");
        $vigente = Documento::query()
            ->where(['persona_id' => $personaId, 'tipo' => TipoDocumento::CertificadoNivel, 'idioma' => $idioma, 'nivel' => $nivel])
            ->whereNull('anulado_en')
            ->value('codigo');
        $duplicado = "La persona ya tiene un certificado vigente del nivel {$nivel->etiqueta()} de {$idioma}";

        if ($vigente !== null) {
            throw new ReglaDeNegocioIncumplida("{$duplicado}: {$vigente}.");
        }

        return $this->emision->emitir(TipoDocumento::CertificadoNivel, $personaId, [
            'idioma' => $idioma, 'nivel' => $nivel->value,
            'horas_lectivas' => array_sum(array_map(fn (NotaDelHistorial $nota): int => $nota->horasLectivas, $ciclos)),
            'nota_final' => HistorialDeNotas::notaFinal($ciclos),
            'detalle' => ['ciclos' => array_map(fn (NotaDelHistorial $nota): array => $nota->comoDetalle(), $ciclos)],
        ], $emisor, "{$duplicado}.");
    }
}
