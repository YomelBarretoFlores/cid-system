<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

// Valid documents that copied a rectified result are flagged for staff, never voided here: voiding stays a manual decision.
final class RevisionDeDocumentos
{
    /**
     * @param  list<int>  $personaIds
     */
    public static function marcar(Acta $acta, array $personaIds): void
    {
        if ($personaIds === []) {
            return;
        }

        Documento::query()
            ->whereIntegerInRaw('persona_id', $personaIds)
            ->where('idioma', $acta->idioma)
            ->whereNull('anulado_en')
            ->whereNull('revisar_desde')
            ->where(fn (Builder $documentos): Builder => $documentos
                ->where(fn (Builder $constancias): Builder => $constancias
                    ->where('tipo', TipoDocumento::ConstanciaNotas)
                    ->whereJsonContains('detalle->notas', [['ciclo_numero' => $acta->ciclo_numero, 'periodo' => $acta->periodo]]))
                ->orWhere(fn (Builder $certificados): Builder => $certificados->where('tipo', TipoDocumento::CertificadoNivel)->where('nivel', $acta->nivel)))
            ->update(['revisar_desde' => now(), 'motivo_revision' => self::motivo($acta)]);
    }

    private static function motivo(Acta $acta): string
    {
        return Str::limit(
            "Se rectificó el acta final del grupo {$acta->grupo_codigo} ({$acta->ciclo_nombre}, {$acta->periodo}) y cambió la nota o la condición del estudiante. Revisar el documento y, si ya no corresponde, anularlo y emitir otro.",
            247,
        );
    }
}
