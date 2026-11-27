<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Models\Pago;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// A payment made before the system may have no receipt file; both viewers treat it as a missing file.
final class ReciboGuardado
{
    public static function ruta(Pago $pago): ?string
    {
        $ruta = $pago->recibo_path;

        return $ruta !== null && Storage::disk('local')->exists($ruta) ? $ruta : null;
    }

    public static function nombreDeDescarga(Pago $pago, string $ruta): string
    {
        return 'recibo-'.Str::slug($pago->numero_operacion).'.'.pathinfo($ruta, PATHINFO_EXTENSION);
    }
}
