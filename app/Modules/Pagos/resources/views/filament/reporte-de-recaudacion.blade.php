@use('App\Modules\Pagos\Data\ReporteDeRecaudacion', 'Reporte')
@use('App\Shared\Ui\Formato')
@php($reporte = $this->resultado())
<x-filament-panels::page>
    <div class="rounded-tarjeta bg-tarjeta p-6">
        {{ $this->form }}
    </div>

    @if (is_string($reporte))
        <x-filament::section>
            <p class="text-sm text-danger-600">{{ $reporte }}</p>
        </x-filament::section>
    @else
        @php($maximo = max([1, ...array_map(fn ($fila) => $fila->centimos, $reporte->porConcepto)]))
        <div class="grid gap-3.5 md:grid-cols-3">
            <x-ciln.cifra titulo="Total recaudado" :valor="'S/ '.Reporte::soles($reporte->totalCentimos)"
                :detalle="$reporte->operaciones.' '.($reporte->operaciones === 1 ? 'operación validada' : 'operaciones validadas')" marca />
            <x-ciln.cifra titulo="Ticket promedio" :valor="'S/ '.Reporte::soles($reporte->ticketPromedioCentimos())" detalle="por operación validada" />
            <x-ciln.cifra titulo="Rango" :valor="Formato::fechaCorta($reporte->filtros->desde).' – '.Formato::fechaCorta($reporte->filtros->hasta)" :detalle="$this->alcance()" tono="oro" />
        </div>

        <div class="grid gap-3.5 lg:grid-cols-2">
            <section class="flex flex-col gap-3 rounded-tarjeta bg-tarjeta px-7 py-6">
                <h2 class="mb-1.5 text-xl font-semibold">Por concepto TUPA / TUSNE</h2>
                @forelse ($reporte->porConcepto as $fila)
                    <div wire:key="concepto-{{ $fila->codigo }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                        <div class="flex h-10 min-w-0 items-center justify-between gap-3 rounded-lg px-3.5"
                            style="width: max(60%, {{ round($fila->centimos / $maximo * 100) }}%); background: color-mix(in oklch, var(--color-oro-400) {{ max(35, 100 - $loop->index * 22) }}%, white)">
                            <span class="truncate font-semibold">{{ $fila->nombre }}</span>
                            <span class="shrink-0 text-[12.5px] font-semibold text-oro-950">{{ $fila->operaciones }} {{ $fila->operaciones === 1 ? 'operación' : 'operaciones' }}</span>
                        </div>
                        <span class="flex flex-col items-end">
                            <span class="font-semibold whitespace-nowrap">S/ {{ Reporte::soles($fila->centimos) }}</span>
                            <span class="text-xs text-apagado">{{ $fila->codigo }} · {{ $fila->fuente }}</span>
                        </span>
                    </div>
                @empty
                    <x-ciln.sin-datos>No hay pagos validados en este rango.</x-ciln.sin-datos>
                @endforelse
            </section>

            <section class="flex flex-col gap-1.5 rounded-tarjeta bg-tarjeta px-7 py-6">
                <h2 class="mb-2 text-xl font-semibold">Por idioma y nivel</h2>
                @forelse ($reporte->porIdiomaYNivel as $fila)
                    <div wire:key="idioma-{{ $fila->idioma }}-{{ $fila->nivel }}" class="grid grid-cols-[minmax(0,1fr)_90px_50px_110px] items-center gap-3 border-b border-separador py-2.5">
                        <span class="flex items-center gap-2.5 font-medium">
                            @if ($fila->idioma !== 'Sin idioma')
                                <x-ciln.hoja-bandera :idioma="$fila->idioma" size="22" />
                            @endif
                            {{ $fila->idioma }}
                        </span>
                        <span class="text-terciario">{{ $fila->nivel }}</span>
                        <span class="text-right text-apagado">{{ $fila->operaciones }}</span>
                        <span class="text-right font-semibold whitespace-nowrap">S/ {{ Reporte::soles($fila->centimos) }}</span>
                    </div>
                @empty
                    <x-ciln.sin-datos>No hay pagos validados en este rango.</x-ciln.sin-datos>
                @endforelse
            </section>
        </div>

        <section class="flex flex-col gap-1 rounded-tarjeta bg-tarjeta px-4 pt-6 pb-4">
            <h2 class="px-3 pb-2.5 text-xl font-semibold">Detalle por estudiante</h2>
            <div class="overflow-x-auto">
                <div class="min-w-[720px]">
                    <div class="grid grid-cols-[minmax(200px,1.4fr)_minmax(140px,1fr)_130px_110px_110px] gap-3.5 px-3 pt-1 pb-2 text-[13px] text-apagado">
                        <span>Estudiante</span><span>Concepto</span><span>Operación</span><span>Fecha</span><span class="text-right">Monto</span>
                    </div>
                    @forelse ($reporte->detalle as $fila)
                        <div wire:key="detalle-{{ $loop->index }}" class="grid grid-cols-[minmax(200px,1.4fr)_minmax(140px,1fr)_130px_110px_110px] items-center gap-3.5 rounded-xl px-3 py-2.5 hover:bg-fila-suave">
                            <span class="flex flex-col"><span class="font-medium">{{ $fila->estudiante }}</span><span class="text-[13px] text-apagado">{{ $fila->documento }}</span></span>
                            <span class="flex items-center gap-2">
                                @if ($fila->idioma !== 'Sin idioma')
                                    <x-ciln.hoja-bandera :idioma="$fila->idioma" size="20" />
                                @endif
                                <span class="flex flex-col"><span>{{ $fila->concepto }}</span><span class="text-[13px] text-apagado">{{ $fila->idioma }} · {{ $fila->nivel }}</span></span>
                            </span>
                            <span class="text-secundario">{{ $fila->numeroOperacion }}</span>
                            <span class="text-secundario">{{ Formato::fechaCorta($fila->fechaPago) }}</span>
                            <span class="text-right font-semibold whitespace-nowrap">S/ {{ Reporte::soles($fila->centimos) }}</span>
                        </div>
                    @empty
                        <x-ciln.sin-datos class="mx-3">No hay pagos validados en el rango elegido.</x-ciln.sin-datos>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</x-filament-panels::page>
