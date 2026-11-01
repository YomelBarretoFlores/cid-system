<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Enums\TipoDocumento;
use Illuminate\Support\Facades\DB;

// Runs inside the issuing transaction: the counter row stays locked until it commits, so two issues never share a number.
final class NumeracionDeDocumentos
{
    public static function siguiente(TipoDocumento $tipo, int $anio): int
    {
        DB::statement('INSERT INTO cer_correlativos (tipo, anio, ultimo, created_at, updated_at) VALUES (?, ?, 0, now(), now()) ON CONFLICT DO NOTHING', [$tipo->value, $anio]);
        $ultimo = (int) DB::table('cer_correlativos')->where(['tipo' => $tipo->value, 'anio' => $anio])->lockForUpdate()->value('ultimo');
        DB::table('cer_correlativos')->where(['tipo' => $tipo->value, 'anio' => $anio])->update(['ultimo' => $ultimo + 1, 'updated_at' => now()]);

        return $ultimo + 1;
    }
}
