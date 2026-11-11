<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

// Same format as the generated column doc_docentes.codigo, so other modules can print it from a stored docente_id.
final class CodigoDeDocente
{
    public static function de(int $docenteId): string
    {
        return 'DOC-'.mb_str_pad((string) $docenteId, 5, '0', STR_PAD_LEFT);
    }
}
