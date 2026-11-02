<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\CiclosDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\HistorialDeNotas;

final readonly class ListarNivelesPorCertificar implements NivelesPorCertificar
{
    public function __construct(private CiclosDelNivel $ciclosDelNivel) {}

    public function __invoke(int $personaId): array
    {
        $certificados = Documento::query()
            ->where(['persona_id' => $personaId, 'tipo' => TipoDocumento::CertificadoNivel])
            ->whereNull('anulado_en')
            ->get(['idioma', 'nivel'])
            ->map(fn (Documento $documento): string => "{$documento->idioma}|{$documento->nivel?->value}")
            ->all();

        $niveles = [];

        foreach (HistorialDeNotas::idiomasDe($personaId) as $idioma) {
            $historial = HistorialDeNotas::de($personaId, $idioma);

            foreach (NivelCurricular::cases() as $nivel) {
                $candidato = new NivelPorCertificar($idioma, $nivel);

                if (HistorialDeNotas::nivelAprobado($historial, ($this->ciclosDelNivel)($idioma, $nivel)) !== null && ! in_array($candidato->clave(), $certificados, true)) {
                    $niveles[] = $candidato;
                }
            }
        }

        return $niveles;
    }
}
