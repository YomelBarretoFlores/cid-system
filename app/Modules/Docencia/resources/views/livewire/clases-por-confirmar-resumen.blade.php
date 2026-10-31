@php($titulo = $pendientes === 1 ? 'Tienes 1 clase por confirmar' : "Tienes {$pendientes} clases por confirmar")
<div @class(['hidden' => $pendientes === 0])>
    @if ($pendientes > 0)
        <x-portal.tarjeta :titulo="$titulo" descripcion="Como delegado, confirma que el docente dictó la clase antes de que venza el plazo.">
            <x-portal.boton :href="route('estudiante.clases')" class="self-start">Confirmar clases <x-heroicon-o-arrow-right aria-hidden="true" class="size-4" /></x-portal.boton>
        </x-portal.tarjeta>
    @endif
</div>
