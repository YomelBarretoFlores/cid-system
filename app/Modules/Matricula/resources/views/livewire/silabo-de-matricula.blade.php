<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Sílabo" :volver="route('estudiante.matriculas')">{{ $silabo->curso }} · Grupo {{ $silabo->grupo }}</x-portal.encabezado>

    <x-portal.hoja titulo="Sílabo" :periodo="$silabo->periodo">
        <x-slot:acciones>
            <x-portal.boton variante="secundario" :href="route('estudiante.matriculas.silabo.pdf', ['matricula' => $this->matricula])" target="_blank" rel="noopener" tamano="compacto">Descargar PDF</x-portal.boton>
        </x-slot:acciones>

        @include('docencia::documentos.silabo')
    </x-portal.hoja>
</div>
