@use('App\Modules\Docencia\Enums\EstadoSilabo')
<x-filament-panels::page>
    @if ($aviso)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$aviso" />
    @endif
    @error('silabo')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    <p class="flex flex-wrap items-center gap-2 text-sm text-secundario">
        <span>Estado del sílabo:</span>
        <x-filament::badge :color="EstadoSilabo::colorDe($estado)">{{ EstadoSilabo::etiquetaDe($estado) }}</x-filament::badge>
        <span>
            @switch($estado)
                @case(EstadoSilabo::Publicado)
                    Tus estudiantes ya pueden leerlo. Puedes corregirlo; cada cambio queda registrado.
                    @break
                @case(EstadoSilabo::Borrador)
                    Tus estudiantes todavía no lo ven. Publícalo cuando esté completo.
                    @break
                @default
                    Aún no lo guardas. Puedes avanzar por partes como borrador.
            @endswitch
        </span>
    </p>

    <form wire:submit="guardar" class="flex flex-col gap-6" novalidate>
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">{{ $estado === EstadoSilabo::Publicado ? 'Guardar cambios' : 'Guardar borrador' }}</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
