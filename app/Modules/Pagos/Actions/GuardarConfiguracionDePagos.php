<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Configuracion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarConfiguracionDePagos
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): Configuracion
    {
        throw_unless($usuario->can(PermisoPago::Configurar->value), AuthorizationException::class);
        $recargo = $this->porcentaje($datos['recargo_matricula_extemporanea'] ?? null);

        return Transaccion::ejecutar(function () use ($datos, $recargo): Configuracion {
            $configuracion = Configuracion::query()->whereKey(Configuracion::FILA)->lockForUpdate()->first() ?? new Configuracion;
            $configuracion->forceFill([
                'id' => Configuracion::FILA,
                'recargo_matricula_extemporanea' => $recargo,
                'permite_pagos_anteriores_al_sistema' => (bool) ($datos['permite_pagos_anteriores_al_sistema'] ?? $configuracion->permite_pagos_anteriores_al_sistema),
            ])->save();

            return $configuracion;
        });
    }

    private function porcentaje(mixed $valor): int
    {
        $texto = is_int($valor) ? (string) $valor : (is_string($valor) ? trim($valor) : '');

        if (preg_match('/^\d{1,3}$/', $texto) !== 1 || (int) $texto > 100) {
            throw new ReglaDeNegocioIncumplida('El recargo por matrícula extemporánea debe ser un porcentaje entero de 0 a 100.');
        }

        return (int) $texto;
    }
}
