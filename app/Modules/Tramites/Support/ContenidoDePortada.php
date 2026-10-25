<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Support\Cache\ContenidoPublico;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

final readonly class ContenidoDePortada
{
    public function __construct(
        private OfertaPublica $ofertaPublica,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private TarifasVigentes $tarifasVigentes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function armar(): array
    {
        // One instant for the cache day and the content, so content of a new day is never stored under the previous one.
        $ahora = CarbonImmutable::now('America/Lima');

        return ContenidoPublico::recordar($ahora->toDateString(), fn (): array => $this->construir($ahora));
    }

    /**
     * @return array<string, mixed>
     */
    private function construir(CarbonImmutable $ahora): array
    {
        $oferta = ($this->ofertaPublica)();
        $grupos = $oferta->grupos ?? [];
        $tarifas = ($this->tarifasVigentes)($ahora->startOfDay());

        return [
            'ahora' => $ahora,
            'oferta' => $oferta,
            'grupos' => $grupos,
            'idiomas' => $this->idiomas($grupos),
            'gruposConVacantes' => count(array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->abierto && $grupo->vacantes > 0)),
            'vinculos' => $this->vinculos($tarifas),
            'tarifasDesde' => $tarifas === [] ? null : max(array_map(fn (TarifaVigente $tarifa): CarbonImmutable => $tarifa->vigenteDesde, $tarifas)),
            'fechas' => $oferta instanceof OfertaDelPeriodo ? $this->fechas($oferta, $ahora) : [],
        ];
    }

    /**
     * @param  list<GrupoOfertado>  $grupos
     * @return list<array{nombre: string, ciclos: int, grupos: int}>
     */
    private function idiomas(array $grupos): array
    {
        $activos = array_filter(($this->catalogoDeIdiomas)(), fn (IdiomaDelCatalogo $idioma): bool => $idioma->activo);

        return array_values(array_map(fn (IdiomaDelCatalogo $idioma): array => [
            'nombre' => $idioma->nombre,
            'ciclos' => count($idioma->ciclos),
            'grupos' => count(array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->idioma === $idioma->nombre)),
        ], $activos));
    }

    /**
     * @param  list<TarifaVigente>  $tarifas
     * @return list<array{clave: string, etiqueta: string, tarifas: list<TarifaVigente>}>
     */
    private function vinculos(array $tarifas): array
    {
        $vinculos = array_map(fn (TipoVinculo $vinculo): array => [
            'clave' => $vinculo->value,
            'etiqueta' => $vinculo->etiqueta(),
            'tarifas' => array_values(array_filter($tarifas, fn (TarifaVigente $tarifa): bool => $tarifa->tipoVinculo === $vinculo)),
        ], TipoVinculo::cases());

        return array_values(array_filter($vinculos, fn (array $vinculo): bool => $vinculo['tarifas'] !== []));
    }

    /**
     * @return list<array{detalle: string, fecha: string, estado: string, color: string}>
     */
    private function fechas(OfertaDelPeriodo $oferta, CarbonImmutable $hoy): array
    {
        return [
            $this->fecha('Matrícula en línea y registro de pagos', $oferta->matriculaInicio, $oferta->matriculaFin, $hoy),
            $this->fecha('Inicio de clases de todos los grupos', $oferta->clasesInicio, $oferta->clasesInicio, $hoy),
            $this->fecha('Fin del periodo y cierre de actas', $oferta->clasesFin, $oferta->clasesFin, $hoy),
        ];
    }

    /**
     * @return array{detalle: string, fecha: string, estado: string, color: string}
     */
    private function fecha(string $detalle, CarbonImmutable $desde, CarbonImmutable $hasta, CarbonImmutable $hoy): array
    {
        // Period dates come from the database in the app timezone, so days are compared as Lima calendar dates.
        $dia = $hoy->toDateString();

        [$estado, $color] = match (true) {
            $dia > $hasta->toDateString() => ['Concluido', 'gray'],
            $dia >= $desde->toDateString() => [$desde->isSameDay($hasta) ? 'Hoy' : 'En curso', 'warning'],
            default => ['Próximo', 'info'],
        };

        return ['detalle' => $detalle, 'fecha' => Formato::rango($desde, $hasta), 'estado' => $estado, 'color' => $color];
    }
}
