<x-portal.tarjeta titulo="Recuperar contraseña" descripcion="Te enviaremos un enlace a tu correo para crear una nueva contraseña.">
    @if ($enviado)
        <x-portal.alerta tipo="exito">Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.</x-portal.alerta>
    @else
        <form wire:submit="enviar" class="flex flex-col gap-4" novalidate>
            <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" required autofocus />
            <x-portal.boton accion="enviar">Enviar enlace</x-portal.boton>
        </form>
    @endif

    <p class="mt-6 text-sm">
        <a href="{{ route('estudiante.ingresar') }}" class="text-ciln-700 underline underline-offset-2 hover:text-ciln-900">Volver a ingresar</a>
    </p>
</x-portal.tarjeta>
