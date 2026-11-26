<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\TipoVinculo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ObtenerVinculoPorDefecto
{
    public const string SIN_POR_DEFECTO = 'No podemos crear cuentas en este momento: el CILN aún no definió el vínculo por defecto. Acércate a la oficina del CILN.';

    public function __invoke(): string
    {
        $codigo = TipoVinculo::query()->where(['por_defecto' => true, 'activo' => true])->value('codigo');

        return is_string($codigo) ? $codigo : throw new ReglaDeNegocioIncumplida(self::SIN_POR_DEFECTO);
    }
}
