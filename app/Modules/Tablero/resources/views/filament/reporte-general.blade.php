@php($reporte = $this->reporte())
<x-filament-panels::page>
    <div class="rounded-tarjeta bg-tarjeta p-6">
        {{ $this->form }}
    </div>

    @if (is_string($reporte))
        <x-filament::section>{{ $reporte }}</x-filament::section>
    @else
        <p class="text-sm text-gray-600">
            <strong>{{ $reporte->periodo }}</strong>
            @foreach ($reporte->alcance as $etiqueta => $valor)
                · {{ $etiqueta }}: {{ $valor }}
            @endforeach
        </p>

        @if (count($reporte->secciones) > 1)
            <x-filament::tabs>
                @foreach ($reporte->secciones as $indice => $seccion)
                    <x-filament::tabs.item :active="$this->seccion === $indice" wire:click="$set('seccion', {{ $indice }})">{{ $seccion->titulo }}</x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        @endif

        {{ $this->table }}

        @php($totales = $reporte->secciones[$this->seccion] ?? null)
        @if ($totales !== null && $totales->totales !== [])
            @php($conTotal = array_filter($totales->columnas, fn ($columna, int $indice): bool => $indice === 0 || collect($totales->totales)->contains(fn (array $fila): bool => $fila[$indice] !== null), ARRAY_FILTER_USE_BOTH))
            <x-filament::section heading="Totales" compact>
                <div style="overflow-x: auto;">
                    <table class="text-sm" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                @foreach ($conTotal as $indice => $columna)
                                    <th class="text-gray-600" style="padding: 4px 8px; font-weight: 500; text-align: {{ $columna->tipo->esNumero() ? 'right' : 'left' }};">{{ $indice === 0 ? '' : $columna->nombre }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($totales->totales as $fila)
                                <tr style="border-top: 1px solid rgba(0, 0, 0, 0.08);">
                                    @foreach ($conTotal as $indice => $columna)
                                        <td style="padding: 4px 8px; font-weight: 600; text-align: {{ $columna->tipo->esNumero() ? 'right' : 'left' }};">{{ $fila[$indice] === null ? '' : $columna->tipo->mostrar($fila[$indice]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
