<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Contracts\HabilitacionPorTramite;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class RegistrarHabilitacionPorTramite implements HabilitacionPorTramite
{
    public function __construct(private CatalogoDeIdiomas $catalogoDeIdiomas) {}

    public function __invoke(int $personaId, int $cicloId, OrigenHabilitacion $origen, int $usuarioId, bool $soloAvanzar): bool
    {
        $idioma = collect(($this->catalogoDeIdiomas)())->first(fn (IdiomaDelCatalogo $idioma): bool => isset($idioma->ciclos[$cicloId]))
            ?? throw new ReglaDeNegocioIncumplida('El ciclo elegido no existe.');
        $orden = array_keys($idioma->ciclos);

        return Transaccion::ejecutar(function () use ($personaId, $cicloId, $origen, $usuarioId, $soloAvanzar, $idioma, $orden): bool {
            MovimientoDeMatricula::bloquearPersona($personaId);
            $actual = Habilitacion::query()->where(['persona_id' => $personaId, 'idioma_id' => $idioma->id])->lockForUpdate()->first();

            if ($actual?->ciclo_id === $cicloId || ($soloAvanzar && $actual !== null && array_search($actual->ciclo_id, $orden, true) > array_search($cicloId, $orden, true))) {
                return false;
            }

            Habilitacion::query()->updateOrCreate(
                ['persona_id' => $personaId, 'idioma_id' => $idioma->id],
                ['ciclo_id' => $cicloId, 'origen' => $origen, 'motivo' => null, 'actualizado_por' => $usuarioId],
            );

            return true;
        });
    }
}
