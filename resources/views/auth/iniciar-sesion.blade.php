<x-portal.acceso :titulo="$entrada->titulo()" descripcion="Usa el correo y la contraseña de tu cuenta del CILN.">
    <form wire:submit="ingresar" class="flex flex-col gap-4" novalidate>
        <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" placeholder="tucorreo@gmail.com" required autofocus />
        <x-portal.campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required />

        <a href="{{ route('password.request') }}" class="enlace -mt-1 self-end">¿Olvidaste tu contraseña?</a>

        <label class="flex items-center gap-2 text-[14.5px] text-secundario">
            <input type="checkbox" wire:model="recordar" class="casilla">
            Mantener la sesión iniciada en este equipo
        </label>

        <x-portal.boton accion="ingresar" class="mt-1 w-full" tamano="grande">Ingresar</x-portal.boton>
    </form>

    <x-slot:pie>
        @if ($entrada === App\Shared\Auth\Enums\EntradaDelPortal::Docente)
            <span>¿Eres estudiante?</span>
            <a href="{{ route('estudiante.ingresar') }}" class="enlace font-semibold">Ingresa al portal del estudiante</a>
        @else
            <span>¿Aún no tienes cuenta?</span>
            <a href="{{ route('estudiante.registro') }}" class="enlace font-semibold">Créala con tu documento y tu correo</a>
            <span class="basis-full">¿Eres docente del CILN? <a href="{{ route('docente.ingresar') }}" class="enlace font-semibold">Ingresa al portal docente</a></span>
        @endif
    </x-slot:pie>
</x-portal.acceso>
