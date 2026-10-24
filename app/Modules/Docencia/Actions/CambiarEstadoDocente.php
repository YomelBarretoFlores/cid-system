<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ReglasDeCargaDocente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class CambiarEstadoDocente
{
    public function __construct(private ReglasDeCargaDocente $reglas) {}

    public function __invoke(int $docenteId, EstadoDocente $estado): Docente
    {
        return DB::transaction(function () use ($docenteId, $estado): Docente {
            $docente = Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();

            if ($docente->estado === $estado) {
                throw new ReglaDeNegocioIncumplida("El docente ya está {$estado->etiqueta()}.");
            }

            if ($estado !== EstadoDocente::Activo) {
                $this->reglas->asegurarSinGruposVigentes($docente);
            }

            $docente->update(['estado' => $estado]);

            return $docente;
        });
    }
}
