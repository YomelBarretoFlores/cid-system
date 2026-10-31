<div class="flex flex-col gap-4 text-sm">
    @unless ($silabo->estaPublicado())
        <x-filament::callout color="warning" heading="Borrador: aún no publicado para los estudiantes." />
    @endunless

    @include('docencia::documentos.silabo')
</div>
