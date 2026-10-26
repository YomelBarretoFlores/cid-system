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
                @forelse (collect($reporte->porConcepto)->sortByDesc('centimos')->values() as $fila)
                    <div wire:key="concepto-{{ $fila->codigo }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                        <div class="flex h-10 min-w-0 items-center justify-between gap-3 rounded-lg px-3.5"
                            style="width: {{ round($fila->centimos / $maximo * 100) }}%; min-width: min(100%, 230px); background: color-mix(in oklch, var(--color-oro-400) {{ max(35, 100 - $loop->index * 22) }}%, white)">
                            <span class="truncate font-semibold">{{ $fila->nombre }}</span>
                            <span class="shrink-0 text-[12.5px] font-semibold text-oro-950" title="{{ $fila->operaciones }} {{ $fila->operaciones === 1 ? 'operación' : 'operaciones' }}">{{ $fila->operaciones }} op.</span>
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

        {{ $this->table }}
    @endif
</x-filament-panels::page>
