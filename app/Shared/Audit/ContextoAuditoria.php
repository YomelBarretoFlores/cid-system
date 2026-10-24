<?php

declare(strict_types=1);

namespace App\Shared\Audit;

use Illuminate\Support\Facades\DB;

final class ContextoAuditoria
{
    public function establecer(int|string|null $usuarioId, ?string $ip, ?string $solicitudId = null): void
    {
        DB::select(
            "select set_config('app.usuario_id', ?, false), set_config('app.ip', ?, false), set_config('app.solicitud_id', ?, false)",
            [(string) ($usuarioId ?? ''), $ip ?? '', $solicitudId ?? ''],
        );
    }

    public function limpiar(): void
    {
        $this->establecer(null, null);
    }
}
