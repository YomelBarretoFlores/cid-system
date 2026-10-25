<x-portal.acceso titulo="Crea una nueva contraseña" descripcion="Debe tener al menos 8 caracteres, con letras y números.">
    <form wire:submit="restablecer" class="flex flex-col gap-4" novalidate>
        <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" required />
        <x-portal.campo nombre="password" etiqueta="Nueva contraseña" tipo="password" autocomplete="new-password" required />
        <x-portal.campo nombre="password_confirmation" etiqueta="Repite la nueva contraseña" tipo="password" autocomplete="new-password" required />
        <x-portal.boton accion="restablecer" class="mt-1 w-full" tamano="grande">Guardar contraseña</x-portal.boton>
    </form>
</x-portal.acceso>
