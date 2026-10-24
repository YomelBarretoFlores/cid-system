<x-portal.tarjeta titulo="Ingresar al portal del estudiante" descripcion="Usa el correo y la contraseña de tu cuenta del CILN.">
    <form wire:submit="ingresar" class="flex flex-col gap-4" novalidate>
        <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" required autofocus />
        <x-portal.campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" wire:model="recordar" class="size-4 rounded border-slate-300 text-ciln-700 focus:outline-ciln-600">
            Mantener la sesión iniciada en este equipo
        </label>

        <x-portal.boton accion="ingresar">Ingresar</x-portal.boton>
    </form>

    <div class="mt-6 flex flex-col gap-2 text-sm">
        <a href="{{ route('password.request') }}" class="text-ciln-700 underline underline-offset-2 hover:text-ciln-900">¿Olvidaste tu contraseña?</a>
        <p class="text-slate-700">¿Aún no tienes cuenta? <a href="{{ route('estudiante.registro') }}" class="font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900">Créala con tu documento y tu correo</a>.</p>
    </div>
</x-portal.tarjeta>
