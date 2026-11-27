{{-- The home page with the panel's unsaved texts, for the editor only. --}}
<x-layouts.base title="Vista previa de la portada" :indexable="false" :marco="false">
    <div role="status" class="border-b border-marino-900/20 bg-ambar-fondo px-4 py-2 text-center text-[14px] text-ambar-texto print:hidden">
        <strong class="font-semibold">Vista previa: los cambios aún no se guardaron.</strong>
        @if ($fotoPendiente)
            La foto nueva se mostrará después de guardar.
        @endif
        Pulse «Editar» en una sección para volver a ella en el formulario; guarde en el panel para publicar.
    </div>
    @include('tramites::livewire.portada-publica', ['atajos' => $atajos])
    {{-- The form tab keeps the unsaved changes: «Editar» moves that tab to the section instead of opening a new form. --}}
    <script>
        document.addEventListener('click', (evento) => {
            const enlace = evento.target.closest('[data-editar-seccion]');
            const formulario = window.opener;

            if (! enlace || ! formulario || formulario.closed) {
                return;
            }

            try {
                const destino = new URL(enlace.href);
                const seccion = formulario.document.getElementById(destino.hash.slice(1));

                // A form tab that moved to another page, or lost the section, gets no jump: the link opens the form instead.
                if (formulario.location.pathname !== destino.pathname || ! seccion) {
                    return;
                }

                formulario.history.replaceState(null, '', destino.hash);
                seccion.scrollIntoView({ block: 'start' });
                formulario.focus();
                evento.preventDefault();
            } catch {
                // Another origin or a closed form: the link opens the form normally.
            }
        });
    </script>
</x-layouts.base>
