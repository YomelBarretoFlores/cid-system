<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Data\ContextoAcademico;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;

final readonly class DescribirContextoAcademicoDePagos implements ContextoAcademicoDePagos
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
    ) {}

    public function __invoke(array $pagoIds): array
    {
        return $this->deMatriculas($pagoIds) + $this->deSolicitudes($pagoIds);
    }

    /**
     * @param  list<int>  $pagoIds
     * @return array<int, ContextoAcademico>
     */
    private function deMatriculas(array $pagoIds): array
    {
        $matriculas = Matricula::query()->whereIn('pago_id', $pagoIds)->get(['pago_id', 'grupo_id']);
        $grupos = ($this->detalleDeGrupos)(array_values($matriculas->pluck('grupo_id')->unique()->all()));
        $contextos = [];

        foreach ($matriculas as $matricula) {
            $grupo = $grupos[$matricula->grupo_id];
            $contextos[(int) $matricula->pago_id] = new ContextoAcademico($grupo->periodoId, $grupo->periodo, $grupo->idioma, $grupo->nivel);
        }

        return $contextos;
    }

    /**
     * @param  list<int>  $pagoIds
     * @return array<int, ContextoAcademico>
     */
    private function deSolicitudes(array $pagoIds): array
    {
        $idiomas = collect(($this->catalogoDeIdiomas)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre]);

        return SolicitudClasificacion::query()
            ->whereIn('pago_id', $pagoIds)
            ->get(['pago_id', 'idioma_id'])
            ->mapWithKeys(fn (SolicitudClasificacion $solicitud): array => [
                (int) $solicitud->pago_id => new ContextoAcademico(null, null, (string) $idiomas->get($solicitud->idioma_id, '—'), null),
            ])
            ->all();
    }
}
