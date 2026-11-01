@php($reporte = $this->reporte())
<x-filament-panels::page>
    <div class="rounded-tarjeta bg-tarjeta p-6">
        {{ $this->form }}
    </div>

    @if (is_string($reporte))
        <x-filament::section>{{ $reporte }}</x-filament::section>
    @else
        @if (count($reporte->secciones) > 1)
            <x-filament::tabs>
                @foreach ($reporte->secciones as $indice => $seccion)
                    <x-filament::tabs.item :active="$this->seccion === $indice" wire:click="$set('seccion', {{ $indice }})">{{ $seccion->titulo }}</x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        @endif

        {{ $this->table }}
    @endif
</x-filament-panels::page>
