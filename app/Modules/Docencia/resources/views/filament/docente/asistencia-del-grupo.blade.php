<x-filament-panels::page>
    @if ($guardada)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$guardada" />
    @endif
    @error('asistencia')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    @if (! $hayClases)
        <x-filament::empty-state heading="Este grupo todavía no tiene clases para registrar." description="Solo se registran clases del horario entre el inicio del periodo y hoy." icon="heroicon-o-calendar-days" />
    @else
        <form wire:submit="guardar" class="flex flex-col gap-6" novalidate>
            {{ $this->form }}

            @if ($hayEstudiantes)
                <div>
                    <x-filament::button type="submit">Guardar asistencia</x-filament::button>
                </div>
            @else
                <x-filament::empty-state heading="El grupo no tiene estudiantes matriculados." icon="heroicon-o-user-group" />
            @endif
        </form>
    @endif
</x-filament-panels::page>
