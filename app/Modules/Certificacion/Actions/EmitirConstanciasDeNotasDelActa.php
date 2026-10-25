<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class EmitirConstanciasDeNotasDelActa
{
    public function __construct(private EmitirConstanciaDeNotas $emitir) {}

    /**
     * @return list<Documento>
     */
    public function __invoke(int $actaId, User $emisor): array
    {
        EmisionDeDocumento::asegurarPermiso($emisor);
        $acta = Acta::query()->find($actaId) ?? throw new ReglaDeNegocioIncumplida('El acta final no existe.');

        if ($acta->estado !== EstadoActa::Cerrada) {
            throw new ReglaDeNegocioIncumplida("El acta final del grupo {$acta->grupo_codigo} está en rectificación; emite las constancias cuando se vuelva a cerrar.");
        }

        return array_values($acta->notas()->orderBy('estudiante_nombre')->pluck('persona_id')
            ->map(fn (int $personaId): Documento => ($this->emitir)($personaId, $acta->idioma, $emisor))
            ->all());
    }
}
