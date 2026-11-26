<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Support\DocumentoValido;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ComprobarDocumento
{
    // Only a new or changed document is checked: editing a rule in the catalog never invalidates numbers already stored.
    public function __invoke(Persona $persona, bool $formal = true): void
    {
        if ($persona->exists && ! $persona->isDirty(['tipo_documento', 'numero_documento'])) {
            return;
        }

        $guardado = $persona->exists ? $persona->getOriginal('tipo_documento') : null;
        $tipo = DocumentoValido::tipo($persona->tipo_documento, is_string($guardado) ? $guardado : null)
            ?? throw new ReglaDeNegocioIncumplida(DocumentoValido::TIPO_NO_DISPONIBLE);
        $error = DocumentoValido::error($tipo, $persona->numero_documento, $formal);

        if ($error !== null) {
            throw new ReglaDeNegocioIncumplida($error);
        }
    }
}
