<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class AutorizarRectificacion
{
    public const int MOTIVO_MINIMO = 5;

    public const int MOTIVO_MAXIMO = 500;

    public function __invoke(int $actaId, string $motivo, User $administrador): void
    {
        $motivo = trim($motivo);
        $largo = mb_strlen($motivo);

        if ($largo < self::MOTIVO_MINIMO || $largo > self::MOTIVO_MAXIMO) {
            throw new ReglaDeNegocioIncumplida('Indica el motivo de la rectificación, de 5 a 500 caracteres.');
        }

        Transaccion::ejecutar(function () use ($actaId, $motivo, $administrador): void {
            $acta = Acta::query()->whereKey($actaId)->lockForUpdate()->firstOrFail();

            if ($acta->estado === EstadoActa::Abierta) {
                throw new ReglaDeNegocioIncumplida("El acta final del grupo {$acta->grupo_codigo} ya tiene una rectificación autorizada.");
            }

            $acta->update(['estado' => EstadoActa::Abierta]);
            Rectificacion::query()->create(['acta_id' => $acta->id, 'motivo' => $motivo, 'autorizada_por' => $administrador->id, 'autorizada_en' => now()]);
        });
    }
}
