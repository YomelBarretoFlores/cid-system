<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Certificacion\Support\HistorialDeNotas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;

// Bulk issue: every student who passed a cycle in a closed acta of the period and has now completed its level gets the certificate once.
final readonly class EmitirCertificadosDelPeriodo
{
    public function __construct(private EmitirCertificadoDeNivel $emitir) {}

    /**
     * @return list<Documento> the certificates issued now; students already certified are skipped
     */
    public function __invoke(int $periodoId, User $emisor): array
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $emitidos = [];

        foreach ($this->candidatos($periodoId) as [$personaId, $idioma, $nivel]) {
            if ($this->yaCertificado($personaId, $idioma, $nivel) || HistorialDeNotas::nivelAprobado(HistorialDeNotas::de($personaId, $idioma), $nivel) === null) {
                continue;
            }

            try {
                $emitidos[] = ($this->emitir)($personaId, $idioma, $nivel, $emisor);
            } catch (ReglaDeNegocioIncumplida $error) {
                // A student refused now (usually certified meanwhile by someone else) is logged and the rest of the period goes on.
                RegistroDeRechazos::registrar($error);
            }
        }

        return $emitidos;
    }

    /**
     * @return list<array{int, string, NivelCurricular}>
     */
    private function candidatos(int $periodoId): array
    {
        return array_values(NotaFinal::query()
            ->join('cer_actas', 'cer_actas.id', '=', 'cer_notas_finales.acta_id')
            ->where('cer_actas.periodo_id', $periodoId)
            ->where('cer_actas.estado', EstadoActa::Cerrada)
            ->where('cer_notas_finales.estado_final', EstadoFinal::Aprobado)
            ->distinct()
            ->orderBy('cer_notas_finales.persona_id')
            ->get(['cer_notas_finales.persona_id', 'cer_actas.idioma', 'cer_actas.nivel'])
            ->map(fn (NotaFinal $nota): array => [$nota->persona_id, (string) $nota->getAttribute('idioma'), NivelCurricular::from((string) $nota->getAttribute('nivel'))])
            ->all());
    }

    private function yaCertificado(int $personaId, string $idioma, NivelCurricular $nivel): bool
    {
        return Documento::query()
            ->where(['persona_id' => $personaId, 'tipo' => TipoDocumento::CertificadoNivel, 'idioma' => $idioma, 'nivel' => $nivel])
            ->whereNull('anulado_en')
            ->exists();
    }
}
