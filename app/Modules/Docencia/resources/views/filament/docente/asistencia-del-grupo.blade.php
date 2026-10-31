<x-filament-panels::page>
    @if ($guardada)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$guardada" />
    @endif
    @error('asistencia')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    <p class="text-sm text-secundario">
        @if ($delegado)
            Delegado: <span class="font-medium text-tinta">{{ $delegado }}</span>
        @else
            Este grupo todavía no tiene delegado.
        @endif
    </p>

    @if (! $hayClases)
        <x-filament::empty-state heading="Este grupo todavía no tiene clases para registrar." description="Solo se registran clases del horario cuyo llamado de inicio ya abrió." icon="heroicon-o-calendar-days" />
    @else
        <form wire:submit="guardar" class="flex flex-col gap-6" novalidate>
            {{ $this->form }}

            @if (! $hayEstudiantes)
                <x-filament::empty-state heading="El grupo no tiene estudiantes matriculados." icon="heroicon-o-user-group" />
            @elseif ($boton)
                <div>
                    <x-filament::button type="submit">{{ $boton }}</x-filament::button>
                </div>
            @endif
        </form>
    @endif
</x-filament-panels::page>
