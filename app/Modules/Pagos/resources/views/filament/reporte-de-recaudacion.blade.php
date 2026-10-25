@use('App\Modules\Pagos\Data\ReporteDeRecaudacion', 'Reporte')
@php($reporte = $this->resultado())
<x-filament-panels::page>
    {{ $this->form }}

    @if (is_string($reporte))
        <x-filament::section>
            <p class="text-sm text-danger-600">{{ $reporte }}</p>
        </x-filament::section>
    @else
        <x-filament::section heading="Resumen" :description="'Del '.$reporte->filtros->desde->format('d/m/Y').' al '.$reporte->filtros->hasta->format('d/m/Y').' · solo pagos validados'">
            <p class="text-kpi font-medium">S/ {{ Reporte::soles($reporte->totalCentimos) }}</p>
            <p class="text-sm text-apagado">{{ $reporte->operaciones }} {{ $reporte->operaciones === 1 ? 'operación' : 'operaciones' }}</p>
        </x-filament::section>

        <x-filament::section heading="Por concepto TUPA/TUSNE">
            <table class="tabla-reporte">
                <thead><tr><th scope="col">Código</th><th scope="col">Concepto</th><th scope="col">Fuente</th><th scope="col" class="text-right">Operaciones</th><th scope="col" class="text-right">Monto</th></tr></thead>
                <tbody>
                    @forelse ($reporte->porConcepto as $fila)
                        <tr wire:key="concepto-{{ $fila->codigo }}">
                            <td>{{ $fila->codigo }}</td><td>{{ $fila->nombre }}</td><td>{{ $fila->fuente }}</td>
                            <td class="text-right">{{ $fila->operaciones }} {{ $fila->operaciones === 1 ? 'operación' : 'operaciones' }}</td><td class="text-right">S/ {{ Reporte::soles($fila->centimos) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-apagado">No hay pagos validados en este rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Por idioma y nivel">
            <table class="tabla-reporte">
                <thead><tr><th scope="col">Idioma</th><th scope="col">Nivel</th><th scope="col" class="text-right">Operaciones</th><th scope="col" class="text-right">Monto</th></tr></thead>
                <tbody>
                    @foreach ($reporte->porIdiomaYNivel as $fila)
                        <tr wire:key="idioma-{{ $fila->idioma }}-{{ $fila->nivel }}">
                            <td>{{ $fila->idioma }}</td><td>{{ $fila->nivel }}</td><td class="text-right">{{ $fila->operaciones }}</td><td class="text-right">S/ {{ Reporte::soles($fila->centimos) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Detalle por estudiante" collapsible>
            <table class="tabla-reporte">
                <thead><tr><th scope="col">Fecha</th><th scope="col">Estudiante</th><th scope="col">Concepto</th><th scope="col">Idioma y nivel</th><th scope="col">Operación</th><th scope="col" class="text-right">Monto</th></tr></thead>
                <tbody>
                    @foreach ($reporte->detalle as $fila)
                        <tr wire:key="detalle-{{ $loop->index }}">
                            <td>{{ $fila->fechaPago->format('d/m/Y') }}</td><td>{{ $fila->documento }} · {{ $fila->estudiante }}</td><td>{{ $fila->concepto }}</td>
                            <td>{{ $fila->idioma }} · {{ $fila->nivel }}</td><td>{{ $fila->numeroOperacion }}</td><td class="text-right">S/ {{ Reporte::soles($fila->centimos) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
