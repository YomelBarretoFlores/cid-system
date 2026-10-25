<x-portal.acceso titulo="Verifica tu correo" descripcion="Antes de continuar, abre el enlace que enviamos a {{ $correo }}.">
    @if ($aviso)
        <x-portal.alerta>{{ $aviso }}</x-portal.alerta>
    @endif

    <p class="text-secundario">¿No te llegó? Revisa la carpeta de spam o pide otro enlace.</p>
    <x-portal.boton type="button" wire:click="reenviar" accion="reenviar" class="w-full" tamano="grande">Enviar otro enlace</x-portal.boton>

    <x-slot:pie>
        <form method="POST" action="{{ route('estudiante.salir') }}">
            @csrf
            <button type="submit" class="enlace">Cerrar sesión</button>
        </form>
    </x-slot:pie>
</x-portal.acceso>
