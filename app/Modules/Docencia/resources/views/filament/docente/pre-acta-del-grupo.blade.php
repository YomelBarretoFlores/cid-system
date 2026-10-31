<x-filament-panels::page>
    @if ($guardadas)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$guardadas" />
    @endif
    @error('notas')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    @if (! $hayEsquema)
        <x-filament::empty-state heading="El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre." icon="heroicon-o-clipboard-document-list" />
    @elseif (! $hayEstudiantes)
        <x-filament::empty-state heading="El grupo no tiene estudiantes matriculados." icon="heroicon-o-user-group" />
    @else
        <form wire:submit="guardar" class="flex flex-col gap-6" novalidate>
            {{ $this->form }}

            <div>
                <x-filament::button type="submit">Guardar notas</x-filament::button>
            </div>
        </form>
    @endif
</x-filament-panels::page>
