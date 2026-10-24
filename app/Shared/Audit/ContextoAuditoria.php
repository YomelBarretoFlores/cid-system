<?php

declare(strict_types=1);

namespace App\Shared\Audit;

use Illuminate\Support\Facades\DB;

final class ContextoAuditoria
{
    public function establecer(int|string|null $usuarioId, ?string $ip): void
    {
        DB::select(
            "select set_config('app.usuario_id', ?, false), set_config('app.ip', ?, false)",
            [(string) ($usuarioId ?? ''), $ip ?? ''],
        );
    }

    public function limpiar(): void
    {
        $this->establecer(null, null);
    }
}
