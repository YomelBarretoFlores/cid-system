<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Matricula\Data\ContextoAcademico;
use App\Modules\Pagos\Data\DetalleDeRecaudacion;
use App\Modules\Pagos\Data\FilaDeConcepto;
use App\Modules\Pagos\Data\FilaDeIdioma;
use App\Modules\Pagos\Data\FiltrosDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class GenerarReporteDeRecaudacion
{
    private const string SIN_IDIOMA = 'Sin idioma';

    private const string SIN_NIVEL = 'Sin nivel';

    private const array ORDEN_DE_NIVELES = ['Básico' => 1, 'Intermedio' => 2, 'Avanzado' => 3];

    public function __construct(
        private ContextoAcademicoDePagos $contextoAcademico,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    public function __invoke(FiltrosDeRecaudacion $filtros): ReporteDeRecaudacion
    {
        if ($filtros->desde->gt($filtros->hasta)) {
            throw new ReglaDeNegocioIncumplida('La fecha inicial no puede ser posterior a la final.');
        }

        $pagos = $this->pagosValidados($filtros);
        $contextos = ($this->contextoAcademico)(array_values($pagos->modelKeys()));

        if ($filtros->periodoId !== null) {
            $pagos = $pagos->filter(fn (Pago $pago): bool => ($contextos[$pago->id] ?? null)?->periodoId === $filtros->periodoId)->values();
        }

        $detalle = $this->detalle($pagos, $contextos);

        return new ReporteDeRecaudacion(
            filtros: $filtros,
            totalCentimos: array_sum(array_map(fn (DetalleDeRecaudacion $fila): int => $fila->centimos, $detalle)),
            operaciones: count($detalle),
            porConcepto: $this->porConcepto($pagos),
            porIdiomaYNivel: $this->porIdiomaYNivel($detalle),
            detalle: $detalle,
        );
    }

    /**
     * @return Collection<int, Pago>
     */
    private function pagosValidados(FiltrosDeRecaudacion $filtros): Collection
    {
        return Pago::query()
            ->with(['concepto', 'medioDePago'])
            ->where('estado', EstadoPago::Validado)
            ->whereBetween('fecha_pago', [$filtros->desde->toDateString(), $filtros->hasta->toDateString()])
            ->when($filtros->conceptoId, fn ($query, int $conceptoId) => $query->where('concepto_id', $conceptoId))
            ->orderBy('fecha_pago')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Pago>  $pagos
     * @param  array<int, ContextoAcademico>  $contextos
     * @return list<DetalleDeRecaudacion>
     */
    private function detalle(Collection $pagos, array $contextos): array
    {
        $fichas = ($this->fichasDePersonas)(array_values($pagos->pluck('persona_id')->unique()->all()));

        return array_values($pagos->map(function (Pago $pago) use ($contextos, $fichas): DetalleDeRecaudacion {
            $contexto = $contextos[$pago->id] ?? null;
            $ficha = $fichas[$pago->persona_id] ?? null;

            return new DetalleDeRecaudacion(
                fechaPago: CarbonImmutable::parse($pago->fecha_pago),
                documento: $ficha->documento ?? '—',
                estudiante: $ficha->nombreCompleto ?? '—',
                concepto: "{$pago->concepto->codigo} · {$pago->concepto->nombre}",
                idioma: $contexto->idioma ?? self::SIN_IDIOMA,
                nivel: $contexto->nivel ?? self::SIN_NIVEL,
                periodo: $contexto->periodo ?? '—',
                medioPago: $pago->medioDePago->nombre,
                numeroOperacion: $pago->numero_operacion,
                centimos: $this->centimos((string) $pago->monto),
            );
        })->all());
    }

    /**
     * @param  Collection<int, Pago>  $pagos
     * @return list<FilaDeConcepto>
     */
    private function porConcepto(Collection $pagos): array
    {
        $filas = [];

        foreach ($pagos as $pago) {
            $concepto = $pago->concepto;
            $previa = $filas[$concepto->codigo] ?? null;
            $filas[$concepto->codigo] = new FilaDeConcepto(
                codigo: $concepto->codigo,
                nombre: $concepto->nombre,
                fuente: $concepto->fuente->etiqueta(),
                centimos: ($previa->centimos ?? 0) + self::centimos((string) $pago->monto),
                operaciones: ($previa->operaciones ?? 0) + 1,
            );
        }

        ksort($filas);

        return array_values($filas);
    }

    /**
     * @param  list<DetalleDeRecaudacion>  $detalle
     * @return list<FilaDeIdioma>
     */
    private function porIdiomaYNivel(array $detalle): array
    {
        $filas = [];

        foreach ($detalle as $fila) {
            $clave = "{$fila->idioma}|{$fila->nivel}";
            $previa = $filas[$clave] ?? null;
            $filas[$clave] = new FilaDeIdioma($fila->idioma, $fila->nivel, ($previa->centimos ?? 0) + $fila->centimos, ($previa->operaciones ?? 0) + 1);
        }

        usort($filas, fn (FilaDeIdioma $a, FilaDeIdioma $b): int => [$a->idioma === self::SIN_IDIOMA, $a->idioma, self::ORDEN_DE_NIVELES[$a->nivel] ?? 9]
            <=> [$b->idioma === self::SIN_IDIOMA, $b->idioma, self::ORDEN_DE_NIVELES[$b->nivel] ?? 9]);

        return $filas;
    }

    // Amounts are positive numeric(12,2) strings; integer cents keep every sum exact.
    private function centimos(string $monto): int
    {
        [$soles, $decimales] = explode('.', $monto.'.');

        return (int) $soles * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0');
    }
}
