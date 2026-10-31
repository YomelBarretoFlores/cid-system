<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Contracts\OfertaDeMatricula;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;

// What the enrollment page offers: groups of the enabled cycles and validated payments not used yet.
final readonly class OpcionesDeMatricula
{
    public function __construct(
        private OfertaDeMatricula $ofertaDeMatricula,
        private PagosValidadosDePersona $pagosValidados,
    ) {}

    /**
     * @return list<FichaDeGrupo>
     */
    public function oferta(int $personaId): array
    {
        return ($this->ofertaDeMatricula)(
            Habilitacion::query()->where('persona_id', $personaId)->pluck('ciclo_id', 'idioma_id')->all(),
        );
    }

    /**
     * @return list<PagoValidado>
     */
    public function pagosDisponibles(int $personaId): array
    {
        $pagos = collect(($this->pagosValidados)($personaId, TipoConcepto::Matricula));
        $usados = Matricula::query()->whereIn('pago_id', $pagos->pluck('id'))->pluck('pago_id');

        return array_values($pagos->whereNotIn('id', $usados)->all());
    }
}
