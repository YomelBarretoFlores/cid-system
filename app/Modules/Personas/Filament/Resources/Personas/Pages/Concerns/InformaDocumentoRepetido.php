<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages\Concerns;

use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

trait InformaDocumentoRepetido
{
    /**
     * @param  Closure(): Model  $guardar
     */
    private function guardarSinRepetirDocumento(Closure $guardar): Model
    {
        try {
            return Transaccion::ejecutar($guardar);
        } catch (UniqueConstraintViolationException $error) {
            // Someone else registered the same document after validation ran.
            throw RestriccionUnica::es($error, 'per_personas_tipo_documento_numero_documento_unique')
                ? ValidationException::withMessages(['data.numero_documento' => PersonaForm::DOCUMENTO_REPETIDO])
                : $error;
        }
    }
}
