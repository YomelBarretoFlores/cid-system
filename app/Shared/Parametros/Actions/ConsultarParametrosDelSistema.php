<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Actions;

use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Data\ValoresDeParametros;
use App\Shared\Parametros\Enums\Parametro;
use App\Shared\Parametros\Models\Parametros;

final class ConsultarParametrosDelSistema implements ParametrosDelSistema
{
    private ?ValoresDeParametros $valores = null;

    // A missing row (a test truncation) falls back to the defaults the migration inserts.
    public function __invoke(): ValoresDeParametros
    {
        if ($this->valores instanceof ValoresDeParametros) {
            return $this->valores;
        }

        $fila = Parametros::query()->find(Parametros::FILA);

        /** @var array<string, int> $valores */
        $valores = $fila?->only(array_column(Parametro::cases(), 'value')) ?? [];

        return $this->valores = ValoresDeParametros::desde($valores);
    }
}
