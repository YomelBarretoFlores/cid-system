<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class AnularDocumento
{
    public function __invoke(int $documentoId, string $motivo, User $usuario): void
    {
        EmisionDeDocumento::asegurarPermiso($usuario);
        $motivo = trim($motivo);
        $largo = mb_strlen($motivo);

        if ($largo < 5 || $largo > 500) {
            throw new ReglaDeNegocioIncumplida('Indica el motivo de la anulación, de 5 a 500 caracteres.');
        }

        Transaccion::ejecutar(function () use ($documentoId, $motivo, $usuario): void {
            $documento = Documento::query()->whereKey($documentoId)->lockForUpdate()->first()
                ?? throw new ReglaDeNegocioIncumplida('El documento no existe.');

            if (! $documento->estaVigente()) {
                throw new ReglaDeNegocioIncumplida("El documento {$documento->codigo} ya está anulado.");
            }

            $documento->update(['anulado_por' => $usuario->id, 'anulado_en' => now(), 'motivo_anulacion' => $motivo]);
        });
    }
}
