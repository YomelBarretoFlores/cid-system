<x-portal.perfil-del-estudiante :ficha="$ficha" :es-docente="$esDocente">
    <x-slot:acciones>
        <x-portal.boton :href="route('estudiante.reportes')" variante="secundario" tamano="compacto" wire:navigate>Resumen académico</x-portal.boton>
        <x-portal.boton :href="route('estudiante.perfil.datos')" variante="secundario" tamano="compacto" wire:navigate>Editar perfil</x-portal.boton>
    </x-slot:acciones>

    <livewire:matricula.resumen-del-estudiante :es-docente="$esDocente" />
</x-portal.perfil-del-estudiante>
