@use('App\Modules\Docencia\Data\LineaDeHoras')
@php($expedientes = $this->resultado())
<x-filament-panels::page>
    {{ $this->form }}

    @if (is_string($expedientes))
        <x-filament::section>
            <p class="text-sm text-danger-600">{{ $expedientes }}</p>
        </x-filament::section>
    @else
        <x-filament::section :heading="$this->periodo()" description="Horas dictadas por el costo por hora vigente de la categoría de cada docente. Monto bruto referencial, sin retenciones de ley.">
            @if (collect($expedientes)->contains(fn ($expediente) => $expediente->tieneHorasSinCosto()))
                <p class="mb-3 text-sm text-warning-600">Las horas sin costo vigente no suman al monto; registra el costo por hora de su categoría.</p>
            @endif

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th scope="col">ID</th><th scope="col">Docente</th><th scope="col">Categoría</th><th scope="col">Idioma y nivel</th>
                        <th scope="col" class="text-right">Horas</th><th scope="col" class="text-right">Costo por hora</th><th scope="col" class="text-right">Monto</th><th scope="col"><span class="sr-only">Descargas</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expedientes as $expediente)
                        @foreach ($expediente->lineas as $linea)
                            <tr wire:key="linea-{{ $expediente->docenteId }}-{{ $loop->index }}" class="border-t border-gray-100 dark:border-white/10">
                                <td class="py-1.5">{{ $loop->first ? $expediente->codigo : '' }}</td>
                                <td>{{ $loop->first ? $expediente->nombre : '' }}</td>
                                <td>{{ $loop->first ? $expediente->categoria : '' }}</td>
                                <td>{{ $linea->idioma }} · {{ $linea->nivel }}</td>
                                <td class="text-right">{{ $linea->horas() }} h</td>
                                <td class="text-right">{{ $linea->costoCentimos === null ? 'Sin costo vigente' : LineaDeHoras::soles($linea->costoCentimos) }}</td>
                                <td class="text-right">{{ $linea->montoCentimos === null ? '—' : LineaDeHoras::soles($linea->montoCentimos) }}</td>
                                <td class="text-right">{{ $loop->first ? ($this->descargarPdfAction)(['docente' => $expediente->docenteId]) : '' }}</td>
                            </tr>
                        @endforeach
                        <tr wire:key="total-{{ $expediente->docenteId }}" class="font-medium">
                            <td colspan="4" class="py-1.5 text-gray-500">Total de {{ $expediente->codigo }}</td>
                            <td class="text-right">{{ $expediente->horasTotales() }} h</td>
                            <td></td>
                            <td class="text-right">{{ LineaDeHoras::soles($expediente->montoTotalCentimos()) }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-2 text-gray-500">No hay clases registradas en este mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
