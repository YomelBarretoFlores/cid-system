<x-portal.tarjeta titulo="Verifica tu correo" descripcion="Antes de continuar, abre el enlace que enviamos a {{ $correo }}.">
    <div class="flex flex-col gap-4">
        @if ($aviso)
            <x-portal.alerta>{{ $aviso }}</x-portal.alerta>
        @endif

        <p class="text-sm text-slate-700">¿No te llegó? Revisa la carpeta de spam o pide otro enlace.</p>
        <x-portal.boton type="button" wire:click="reenviar" accion="reenviar">Enviar otro enlace</x-portal.boton>
    </div>
</x-portal.tarjeta>
