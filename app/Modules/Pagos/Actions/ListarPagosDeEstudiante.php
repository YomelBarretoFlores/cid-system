<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Models\Pago;
use Illuminate\Database\Eloquent\Collection;

// A student's own payments, newest first, for the portal page and the API.
final readonly class ListarPagosDeEstudiante
{
    /**
     * @return Collection<int, Pago>
     */
    public function __invoke(int $personaId): Collection
    {
        return Pago::query()
            ->with(['concepto:id,nombre', 'medioDePago:id,codigo,nombre'])
            ->where('persona_id', $personaId)
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->get();
    }
}
