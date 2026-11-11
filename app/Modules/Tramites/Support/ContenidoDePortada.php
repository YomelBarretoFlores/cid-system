<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Matricula\Data\VigenciaDeClasificacion;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Pagos\Enums\TipoConcepto;
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
        $idiomas = $this->idiomas($grupos, $this->menorMonto($tarifas, TipoConcepto::Matricula));
        $gruposConVacantes = count(array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->abierto && $grupo->vacantes > 0));

        return [
            'ahora' => $ahora,
            'oferta' => $oferta,
            'grupos' => $grupos,
            'idiomas' => $idiomas,
            'gruposConVacantes' => $gruposConVacantes,
            'cifras' => $this->cifras($idiomas, $gruposConVacantes, $oferta),
            'examen' => [
                'tarifas' => array_values(array_filter($tarifas, fn (TarifaVigente $tarifa): bool => $tarifa->tipo === TipoConcepto::ExamenClasificacion)),
                'diasDeVigencia' => VigenciaDeClasificacion::DIAS,
            ],
            'vinculos' => $this->vinculos($tarifas),
            'tarifasDesde' => $tarifas === [] ? null : max(array_map(fn (TarifaVigente $tarifa): CarbonImmutable => $tarifa->vigenteDesde, $tarifas)),
            'fechas' => $oferta instanceof OfertaDelPeriodo ? $this->fechas($oferta, $ahora) : [],
        ];
    }

    /**
     * @param  list<GrupoOfertado>  $grupos
     * @return list<array{nombre: string, ciclos: int, grupos: int, horas: array{int, int}|null, modalidades: list<string>, matriculaDesde: string|null}>
     */
    private function idiomas(array $grupos, ?string $matriculaDesde): array
    {
        $activos = array_filter(($this->catalogoDeIdiomas)(), fn (IdiomaDelCatalogo $idioma): bool => $idioma->activo);

        return array_values(array_map(function (IdiomaDelCatalogo $idioma) use ($grupos, $matriculaDesde): array {
            $suyos = array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->idioma === $idioma->nombre);
            $modalidades = array_unique(array_map(fn (GrupoOfertado $grupo): string => $grupo->modalidad, $suyos));

            return [
                'nombre' => $idioma->nombre,
                'ciclos' => count($idioma->ciclos),
                'grupos' => count($suyos),
                'horas' => $idioma->horasPorCiclo === [] ? null : [min($idioma->horasPorCiclo), max($idioma->horasPorCiclo)],
                'modalidades' => array_values(array_filter(
                    array_map(fn (Modalidad $modalidad): string => $modalidad->etiqueta(), Modalidad::cases()),
                    fn (string $etiqueta): bool => in_array($etiqueta, $modalidades, true),
                )),
                'matriculaDesde' => $matriculaDesde,
            ];
        }, $activos));
    }

    /**
     * @param  list<array{nombre: string, ciclos: int, grupos: int, horas: array{int, int}|null, modalidades: list<string>, matriculaDesde: string|null}>  $idiomas
     * @return list<array{etiqueta: string, valor: string, contar: bool}>
     */
    private function cifras(array $idiomas, int $gruposConVacantes, ?OfertaDelPeriodo $oferta): array
    {
        $ciclos = $idiomas === [] ? 0 : max(array_column($idiomas, 'ciclos'));

        return [
            ['etiqueta' => 'Idiomas', 'valor' => (string) count($idiomas), 'contar' => true],
            ['etiqueta' => 'Grupos con vacantes', 'valor' => (string) $gruposConVacantes, 'contar' => true],
            ['etiqueta' => 'Ciclos por idioma', 'valor' => (string) $ciclos, 'contar' => true],
            ['etiqueta' => 'Inicio de clases', 'valor' => $oferta instanceof OfertaDelPeriodo ? Formato::diaYMes($oferta->clasesInicio) : 'Por anunciar', 'contar' => false],
        ];
    }

    /**
     * @param  list<TarifaVigente>  $tarifas
     */
    private function menorMonto(array $tarifas, TipoConcepto $tipo): ?string
    {
        $menor = null;

        foreach ($tarifas as $tarifa) {
            if ($tarifa->tipo === $tipo && is_numeric($tarifa->monto) && ($menor === null || bccomp($tarifa->monto, $menor, 2) < 0)) {
                $menor = $tarifa->monto;
            }
        }

        return $menor;
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
