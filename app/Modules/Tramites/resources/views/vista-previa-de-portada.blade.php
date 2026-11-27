{{-- The home page with the panel's unsaved texts, for the editor only; no edit shortcuts. --}}
<x-layouts.base title="Vista previa de la portada" :indexable="false" :marco="false">
    <div role="status" class="border-b border-marino-900/20 bg-ambar-fondo px-4 py-2 text-center text-[14px] text-ambar-texto print:hidden">
        <strong class="font-semibold">Vista previa: los cambios aún no se guardaron.</strong>
        @if ($fotoPendiente)
            La foto nueva se mostrará después de guardar.
        @endif
        Cierre esta pestaña y guarde en el panel para publicarlos.
    </div>
    @include('tramites::livewire.portada-publica', ['atajos' => []])
</x-layouts.base>
