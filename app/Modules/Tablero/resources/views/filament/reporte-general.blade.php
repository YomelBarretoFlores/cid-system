@php($reporte = $this->reporte())
<x-filament-panels::page>
    <div class="rounded-tarjeta bg-tarjeta p-6">
        {{ $this->form }}
    </div>

    @if (is_string($reporte))
        <x-filament::section>{{ $reporte }}</x-filament::section>
    @else
        <section class="flex min-w-0 flex-col gap-4 border border-piedra-300 bg-tarjeta p-4 text-[13px] sm:p-7">
            <p class="titulo-formal">{{ $reporte->titulo }} <span class="text-apagado">{{ $reporte->periodo }}</span></p>
            <p>
                @foreach ($reporte->alcance as $etiqueta => $valor)
                    <strong>{{ $etiqueta }}:</strong> {{ $valor }}@unless ($loop->last) · @endunless
                @endforeach
            </p>
            <div class="flex flex-col gap-3 overflow-x-auto">
                @include('tablero::documentos.reporte-general')
            </div>
        </section>
    @endif
</x-filament-panels::page>
