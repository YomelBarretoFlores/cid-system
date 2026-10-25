@use('App\Modules\Pagos\Enums\PermisoPago')
@use('App\Modules\Tablero\Support\Cifras')
@use('App\Modules\Tablero\Support\GraficoDeLinea')
@use('App\Shared\Ui\Formato')
@php
    $tablero = $this->tablero();
    $porcentaje = Cifras::porcentaje(...);
    $variacion = fn (string $comparacion, ?float $valor, $punto): string => $valor === null
        ? "{$comparacion}: sin periodo comparable"
        : Cifras::variacion($valor)." frente a {$punto->periodo}";
    $soles = Cifras::soles(...);
    $horas = Cifras::horas(...);
    $puedeVerPagos = auth()->user()?->can(PermisoPago::Ver->value) ?? false;
    $puedeReportar = auth()->user()?->can(PermisoPago::Reportar->value) ?? false;
@endphp
<x-filament-panels::page>
    <div class="rounded-tarjeta bg-tarjeta p-6">
        {{ $this->form }}
    </div>

    @if (is_string($tablero))
        <x-filament::section>
            <p class="text-sm text-danger-600">{{ $tablero }}</p>
        </x-filament::section>
    @else
        @php
            $matriculas = $tablero->matriculas;
            $pagos = $tablero->pagosPorValidar;
            $espera = match ($pagos->diasDelMasAntiguo) {
                0 => 'el más antiguo se registró hoy',
                1 => 'el más antiguo espera 1 día',
                default => 'el más antiguo espera '.$pagos->diasDelMasAntiguo.' días',
            };
            $grafico = GraficoDeLinea::trazos(array_map(fn ($punto) => $punto->total, $matriculas->serie));
            $lider = $matriculas->porIdioma[0] ?? null;
            $franja = $tablero->demandaHoraria[0] ?? null;
            $nivelCritico = collect($tablero->desercion->porNivel)->filter(fn ($fila) => $fila->retiradas > 0)->sortByDesc(fn ($fila) => $fila->tasa())->first();
            $maximoIdioma = max([1, ...array_map(fn ($fila) => $fila->total, $matriculas->porIdioma)]);
            $escala = max([1, ...array_map(fn ($fila) => max($fila->ingresosCentimos, $fila->costoCentimos), $tablero->rentabilidad->filas)]);
        @endphp

        <p class="px-1.5 text-[15px] text-secundario">{{ $tablero->alcance() }}</p>

        <div class="grid gap-3.5 md:grid-cols-3">
            <x-ciln.cifra titulo="Recaudación" :valor="$soles($tablero->recaudacion->totalCentimos)" marca
                :detalle="$tablero->recaudacion->operaciones.' '.($tablero->recaudacion->operaciones === 1 ? 'pago validado' : 'pagos validados').' del periodo'">
                @if ($puedeReportar)
                    <a href="{{ route('filament.admin.pages.reporte-de-recaudacion') }}" class="font-medium text-enlace hover:text-enlace-hover">Reporte de recaudación →</a>
                @endif
            </x-ciln.cifra>

            <x-ciln.cifra titulo="Por validar" :valor="$pagos->porValidar"
                :detalle="$pagos->porValidar === 0 ? 'Todo al día.' : $soles($pagos->centimosPorValidar).' · '.$espera">
                <span class="text-sm text-apagado">Tasa del periodo en todos los idiomas y niveles: {{ $porcentaje($pagos->tasa()) }} ({{ $pagos->pendientes }} de {{ $pagos->registrados }} pagos registrados)</span>
                @if ($puedeVerPagos)
                    <a href="{{ route('filament.admin.resources.pagos.index') }}" class="font-medium text-enlace hover:text-enlace-hover">Todos los pagos →</a>
                @endif
            </x-ciln.cifra>

            <x-ciln.cifra titulo="Matrículas" :valor="$matriculas->total"
                :detalle="$variacion('Mes a mes', $matriculas->crecimientoMensual(), $matriculas->anterior)">
                <span class="text-sm text-apagado">{{ $variacion('Año a año', $matriculas->crecimientoAnual(), $matriculas->delAnioAnterior) }}</span>
            </x-ciln.cifra>
        </div>

        <div class="grid gap-3.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <section class="flex flex-col gap-1 rounded-tarjeta bg-tarjeta px-4 pt-6 pb-4">
                <h2 class="px-3 pb-2 text-xl font-semibold">Pagos por validar</h2>
                @forelse ($pagos->masAntiguos as $pago)
                    <div wire:key="por-validar-{{ $pago->pagoId }}" class="flex items-center gap-3.5 rounded-xl px-3 py-2.5 hover:bg-fila-suave">
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate font-medium">{{ $pago->concepto }}</span>
                            <span class="text-[13.5px] text-apagado">{{ $soles($pago->centimos) }} · pagado el {{ Formato::fechaCorta($pago->fechaPago) }}</span>
                        </span>
                        <span class="shrink-0 text-[13px] text-apagado">{{ $pago->dias === 0 ? 'hoy' : "hace {$pago->dias} ".($pago->dias === 1 ? 'día' : 'días') }}</span>
                        @if ($puedeVerPagos)
                            <a href="{{ route('filament.admin.resources.pagos.view', ['record' => $pago->pagoId]) }}" class="shrink-0 font-medium text-enlace hover:text-enlace-hover" aria-label="Ver el pago">›</a>
                        @endif
                    </div>
                @empty
                    <x-ciln.sin-datos>Todo al día. No hay pagos por validar.</x-ciln.sin-datos>
                @endforelse
            </section>

            <div class="flex min-w-0 flex-col gap-3.5">
                <section class="flex flex-col gap-3 rounded-tarjeta bg-tarjeta px-6 pt-6 pb-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-xl font-semibold">Crecimiento</h2>
                        <span class="text-secundario">Matrículas por periodo</span>
                    </div>
                    @if (count($matriculas->serie) > 1)
                        <svg role="img" aria-label="Matrículas por periodo" class="h-[170px] w-full" viewBox="0 0 {{ GraficoDeLinea::ANCHO }} {{ GraficoDeLinea::ALTO }}" preserveAspectRatio="none">
                            <path d="{{ $grafico['area'] }}" fill="var(--color-verde-fondo)" />
                            <path d="{{ $grafico['linea'] }}" fill="none" stroke="var(--color-positivo)" stroke-width="2.5" vector-effect="non-scaling-stroke" />
                        </svg>
                        <div class="flex justify-between gap-2 text-[13px] text-apagado">
                            <span>{{ $matriculas->serie[0]->periodo }}</span>
                            <span>{{ $matriculas->serie[count($matriculas->serie) - 1]->periodo }}</span>
                        </div>
                        <ul class="sr-only">
                            @foreach ($matriculas->serie as $punto)
                                <li>{{ $punto->periodo }}: {{ $punto->total }}</li>
                            @endforeach
                        </ul>
                    @else
                        <x-ciln.sin-datos>Aún no hay periodos anteriores para comparar.</x-ciln.sin-datos>
                    @endif
                </section>

                <div class="grid gap-3.5 sm:grid-cols-3">
                    <div class="flex flex-col gap-2.5 rounded-[20px] bg-tarjeta p-[18px]">
                        <span class="text-apagado">Deserción</span>
                        <span class="text-2xl font-semibold">{{ $porcentaje($tablero->desercion->total->tasa()) }}</span>
                        <span class="text-[13.5px] text-secundario">{{ $nivelCritico ? "mayor en {$nivelCritico->etiqueta}" : 'sin retiros en el periodo' }}</span>
                    </div>
                    <div class="flex flex-col gap-2.5 rounded-[20px] bg-tarjeta p-[18px]">
                        <span class="text-apagado">Idioma líder</span>
                        @if ($lider)
                            <span class="flex items-center gap-2 text-2xl font-semibold"><x-ciln.hoja-bandera :idioma="$lider->idioma" size="26" />{{ $lider->idioma }}</span>
                            <span class="text-[13.5px] text-secundario">{{ $lider->total }} {{ $lider->total === 1 ? 'matrícula' : 'matrículas' }}</span>
                        @else
                            <span class="text-2xl font-semibold">—</span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-2.5 rounded-[20px] bg-tarjeta p-[18px]">
                        <span class="text-apagado">Demanda horaria</span>
                        <span class="text-2xl font-semibold text-oro-800">{{ $franja?->horaInicio ?? '—' }}</span>
                        <span class="text-[13.5px] font-medium text-oro-600">{{ $franja ? 'el horario más pedido' : 'sin horarios en el periodo' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-3.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
            <section class="flex flex-col gap-3 rounded-tarjeta bg-tarjeta px-7 py-6">
                <h2 class="mb-1.5 text-xl font-semibold">Matrículas por idioma</h2>
                @forelse ($matriculas->porIdioma as $fila)
                    <div wire:key="idioma-{{ $fila->idioma }}" class="flex h-10 items-center justify-between gap-3 rounded-lg px-3.5"
                        style="width: {{ round($fila->total / $maximoIdioma * 100) }}%; min-width: min(100%, 210px); background: color-mix(in oklch, var(--color-oro-400) {{ max(35, 100 - $loop->index * 18) }}%, white)">
                        <span class="flex min-w-0 items-center gap-2.5 font-semibold"><x-ciln.hoja-bandera :idioma="$fila->idioma" size="24" /><span class="truncate">{{ $fila->idioma }}</span></span>
                        <span class="shrink-0 font-semibold text-oro-950">{{ $fila->total }}</span>
                    </div>
                @empty
                    <x-ciln.sin-datos>No hay matrículas en este periodo.</x-ciln.sin-datos>
                @endforelse
            </section>

            <section class="flex flex-col gap-4 rounded-tarjeta bg-tarjeta px-7 py-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-xl font-semibold">Rentabilidad por grupo</h2>
                    <span class="text-secundario">Ingresos por matrícula frente a horas dictadas × costo</span>
                </div>
                @forelse ($tablero->rentabilidad->filas as $fila)
                    @php($margen = $fila->margen())
                    <div wire:key="rentabilidad-{{ $fila->grupoId }}" class="grid grid-cols-[110px_minmax(0,1fr)_100px] items-center gap-3">
                        <span class="flex items-center gap-2 text-sm font-semibold"><x-ciln.hoja-bandera :idioma="$fila->idioma" size="18" />{{ $fila->codigo }}</span>
                        <span class="flex h-2.5 overflow-hidden rounded-full bg-piedra-100" title="Ingresos {{ $soles($fila->ingresosCentimos) }} · costo {{ $soles($fila->costoCentimos) }}">
                            <span class="bg-oro-200" style="width: {{ round(min($fila->ingresosCentimos, $fila->costoCentimos) / $escala * 100, 1) }}%"></span>
                            <span @class(['bg-positivo' => $margen >= 0, 'bg-peligro' => $margen < 0]) style="width: {{ round(abs($margen) / $escala * 100, 1) }}%"></span>
                        </span>
                        <span @class(['text-right text-sm font-semibold whitespace-nowrap', 'text-verde-texto' => $margen >= 0, 'text-rojo-texto' => $margen < 0])>{{ $margen >= 0 ? '+' : '' }}{{ $soles($margen) }}</span>
                    </div>
                @empty
                    <x-ciln.sin-datos>No hay grupos en este periodo.</x-ciln.sin-datos>
                @endforelse
                @if ($tablero->rentabilidad->horasSinCostoCentesimas > 0)
                    <p class="text-sm text-ambar-texto">{{ $horas($tablero->rentabilidad->horasSinCostoCentesimas) }} dictadas no tienen costo por hora vigente y no se incluyen en el costo.</p>
                @endif
            </section>
        </div>

        @include('tablero::filament.partes.tablas-de-resumen')
    @endif
</x-filament-panels::page>
