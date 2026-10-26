<x-portal.acceso titulo="Recuperar contraseña" descripcion="Te enviaremos un enlace a tu correo para crear una nueva contraseña.">
    @if ($enviado)
        <x-portal.alerta tipo="exito">Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.</x-portal.alerta>
    @else
        <form wire:submit="enviar" class="flex flex-col gap-4" novalidate>
            <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" required autofocus />
            <x-portal.boton accion="enviar" class="mt-1 w-full" tamano="grande">Enviar enlace</x-portal.boton>
        </form>
    @endif

    <x-slot:pie>
        <a href="{{ route('ingresar') }}" class="enlace">Volver a ingresar</a>
    </x-slot:pie>
</x-portal.acceso>
