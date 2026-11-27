<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDeModalidades;
use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Data\ModalidadDelCatalogo;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use App\Modules\Certificacion\Data\DatosDeLaInstitucion;
use App\Modules\Pagos\Contracts\MediosDePago;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\MedioDePagoDelCatalogo;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Data\TipoDeVinculo;
use App\Modules\Tramites\Actions\ConsultarTextosDePortada;
use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Support\Cache\ContenidoPublico;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

final readonly class ContenidoDePortada
{
    public function __construct(
        private OfertaPublica $ofertaPublica,
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private TarifasVigentes $tarifasVigentes,
        private CatalogoDeVinculos $catalogoDeVinculos,
        private CatalogoDeModalidades $catalogoDeModalidades,
        private DatosInstitucionales $datosInstitucionales,
        private MediosDePago $mediosDePago,
        private ConsultarTextosDePortada $textosDePortada,
        private ParametrosDelSistema $parametrosDelSistema,
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
        $catalogo = ($this->catalogoDeModalidades)();
        $idiomas = $this->idiomas($grupos, $catalogo, $this->menorMonto($tarifas, TipoConcepto::Matricula));
        $gruposConVacantes = count(array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->abierto && $grupo->vacantes > 0));
        $institucion = ($this->datosInstitucionales)();
        $parametros = ($this->parametrosDelSistema)();

        return [
            ...$this->textos($institucion),
            'umbralDePocasVacantes' => $parametros->umbralPocasVacantes,
            'institucion' => ['universidad' => $institucion->universidad, 'centro' => $institucion->centro, 'ciudad' => $institucion->ciudad],
            'ahora' => $ahora,
            'oferta' => $oferta,
            'grupos' => $grupos,
            'modalidades' => $this->modalidades($grupos, $catalogo),
            'idiomas' => $idiomas,
            'gruposConVacantes' => $gruposConVacantes,
            'cifras' => $this->cifras($idiomas, $gruposConVacantes, $oferta),
            'examen' => [
                'tarifas' => array_values(array_filter($tarifas, fn (TarifaVigente $tarifa): bool => $tarifa->tipo === TipoConcepto::ExamenClasificacion)),
                'diasDeVigencia' => $parametros->diasVigenciaClasificacion,
            ],
            'vinculos' => $this->vinculos($tarifas),
            'tarifasDesde' => $tarifas === [] ? null : max(array_map(fn (TarifaVigente $tarifa): CarbonImmutable => $tarifa->vigenteDesde, $tarifas)),
            'fechas' => $oferta instanceof OfertaDelPeriodo ? $this->fechas($oferta, $ahora) : [],
        ];
    }

    /**
     * The staff-edited texts, with the payment places of the catalog in place of their marker.
     *
     * @return array<string, mixed>
     */
    private function textos(DatosDeLaInstitucion $institucion): array
    {
        $textos = ($this->textosDePortada)();
        $lugares = $this->lugaresDePago();

        return [
            'nosotros' => array_values(array_filter(array_map(trim(...), preg_split('/\R\s*\R/u', $textos->nosotros) ?: []), fn (string $parrafo): bool => $parrafo !== '')),
            'pasos' => $this->conLugares($textos->pasosMatricula, $lugares),
            'pasosDeClasificacion' => $this->conLugares($textos->pasosClasificacion, $lugares),
            'niveles' => $textos->niveles,
            'preguntas' => $this->conLugares($textos->preguntas, $lugares),
            'portada' => [
                'foto' => FotoDePortada::url($textos->fotoPortada),
                'subtitulo' => $textos->subtitulo,
                'botonPrincipal' => $textos->botonPrincipal,
                'botonSecundario' => $textos->botonSecundario,
            ],
            'apartados' => array_map(
                fn (array $apartado): array => [...$apartado, 'introduccion' => $apartado['introduccion'] === null ? null : str_replace(TextosDeLaPortada::LUGARES_DE_PAGO, $lugares, $apartado['introduccion'])],
                $textos->secciones,
            ),
            'pie' => [
                'descripcion' => $textos->pie,
                'contacto' => array_filter(['Dirección' => $institucion->direccion, 'Teléfono' => $institucion->telefono, 'Correo' => $textos->correo, 'Horario de atención' => $textos->horarioAtencion]),
                'redes' => $textos->redes,
            ],
        ];
    }

    /**
     * @param  list<array<string, string>>  $filas
     * @return list<array<string, string>>
     */
    private function conLugares(array $filas, string $lugares): array
    {
        return array_map(
            fn (array $fila): array => array_map(fn (string $texto): string => str_replace(TextosDeLaPortada::LUGARES_DE_PAGO, $lugares, $texto), $fila),
            $filas,
        );
    }

    // "Otro" is the cashier's catch-all channel, not a place to point the public to.
    private function lugaresDePago(): string
    {
        $nombres = array_map(
            fn (MedioDePagoDelCatalogo $medio): string => $medio->nombre,
            array_filter(($this->mediosDePago)(), fn (MedioDePagoDelCatalogo $medio): bool => $medio->codigo !== 'otro'),
        );

        return $nombres === [] ? 'los lugares que indique el CILN' : Arr::join($nombres, ', ', ' o ');
    }

    /**
     * @param  list<GrupoOfertado>  $grupos
     * @param  list<ModalidadDelCatalogo>  $catalogo
     * @return list<array{nombre: string, ciclos: int, grupos: int, horas: array{int, int}|null, modalidades: list<string>, matriculaDesde: string|null}>
     */
    private function idiomas(array $grupos, array $catalogo, ?string $matriculaDesde): array
    {
        $activos = array_filter(($this->catalogoDeIdiomas)(), fn (IdiomaDelCatalogo $idioma): bool => $idioma->activo);

        return array_values(array_map(function (IdiomaDelCatalogo $idioma) use ($grupos, $catalogo, $matriculaDesde): array {
            $suyos = array_filter($grupos, fn (GrupoOfertado $grupo): bool => $grupo->idioma === $idioma->nombre);
            $modalidades = array_unique(array_map(fn (GrupoOfertado $grupo): string => $grupo->modalidad, $suyos));

            return [
                'nombre' => $idioma->nombre,
                'ciclos' => count($idioma->ciclos),
                'grupos' => count($suyos),
                'horas' => $idioma->horasPorCiclo === [] ? null : [min($idioma->horasPorCiclo), max($idioma->horasPorCiclo)],
                'modalidades' => array_values(array_filter(
                    array_map(fn (ModalidadDelCatalogo $modalidad): string => $modalidad->nombre, $catalogo),
                    fn (string $nombre): bool => in_array($nombre, $modalidades, true),
                )),
                'matriculaDesde' => $matriculaDesde,
            ];
        }, $activos));
    }

    /**
     * @param  list<array{nombre: string, ciclos: int, grupos: int, horas: array{int, int}|null, modalidades: list<string>, matriculaDesde: string|null}>  $idiomas
     * @return list<array{etiqueta: string, valor: string}>
     */
    private function cifras(array $idiomas, int $gruposConVacantes, ?OfertaDelPeriodo $oferta): array
    {
        $ciclos = $idiomas === [] ? 0 : max(array_column($idiomas, 'ciclos'));

        return [
            ['etiqueta' => 'Idiomas', 'valor' => (string) count($idiomas)],
            ['etiqueta' => 'Grupos con vacantes', 'valor' => (string) $gruposConVacantes],
            ['etiqueta' => 'Ciclos por idioma', 'valor' => (string) $ciclos],
            ['etiqueta' => 'Inicio de clases', 'valor' => $oferta instanceof OfertaDelPeriodo ? Formato::diaYMes($oferta->clasesInicio) : 'Por anunciar'],
        ];
    }

    /**
     * Until a group is published the home states the first active modality of the catalog (presencial as shipped).
     *
     * @param  list<GrupoOfertado>  $grupos
     * @param  list<ModalidadDelCatalogo>  $catalogo
     * @return list<array{nombre: string, descripcion: ?string}> plain arrays: the cache unserializes only the classes it allows
     */
    private function modalidades(array $grupos, array $catalogo): array
    {
        $publicadas = array_map(fn (GrupoOfertado $grupo): string => $grupo->modalidad, $grupos);
        $modalidades = array_values(array_filter($catalogo, fn (ModalidadDelCatalogo $modalidad): bool => in_array($modalidad->nombre, $publicadas, true)));
        $activas = array_values(array_filter($catalogo, fn (ModalidadDelCatalogo $modalidad): bool => $modalidad->activo));

        return array_map(
            fn (ModalidadDelCatalogo $modalidad): array => ['nombre' => $modalidad->nombre, 'descripcion' => $modalidad->descripcion],
            $modalidades === [] ? array_slice($activas, 0, 1) : $modalidades,
        );
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
        $vinculos = array_map(fn (TipoDeVinculo $vinculo): array => [
            'clave' => $vinculo->codigo,
            'etiqueta' => $vinculo->nombre,
            'tarifas' => array_values(array_filter($tarifas, fn (TarifaVigente $tarifa): bool => $tarifa->tipoVinculo === $vinculo->codigo)),
        ], ($this->catalogoDeVinculos)());

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
