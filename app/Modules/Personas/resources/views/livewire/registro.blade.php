<x-portal.acceso titulo="Crea tu cuenta de estudiante" descripcion="Si ya estás registrado en el CILN, usa el mismo correo que diste en la oficina.">
    <form wire:submit="registrarse" class="flex flex-col gap-6" novalidate>
        @error('registro')
            <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
        @enderror

        <fieldset class="grid gap-3 sm:grid-cols-2">
            <legend class="mb-3 text-xs font-medium tracking-[0.12em] text-grupo uppercase">Documento de identidad</legend>
            <x-portal.seleccion nombre="tipo_documento" etiqueta="Tipo de documento" :opciones="$tiposDeDocumento" />
            <x-portal.campo nombre="numero_documento" etiqueta="Número de documento" autocomplete="off" inputmode="text" required />
        </fieldset>

        <fieldset class="grid gap-3 sm:grid-cols-2">
            <legend class="mb-3 text-xs font-medium tracking-[0.12em] text-grupo uppercase">Datos personales</legend>
            <x-portal.campo nombre="apellido_paterno" etiqueta="Apellido paterno" autocomplete="family-name" required />
            <x-portal.campo nombre="apellido_materno" etiqueta="Apellido materno (opcional)" autocomplete="off" />
            <x-portal.campo nombre="nombres" etiqueta="Nombres" autocomplete="given-name" required />
            <x-portal.fecha nombre="fecha_nacimiento" etiqueta="Fecha de nacimiento" :max="now('America/Lima')->toDateString()" />
            <x-portal.campo nombre="celular" etiqueta="Celular" tipo="tel" autocomplete="tel" inputmode="tel" required />
        </fieldset>

        <fieldset class="grid gap-3 sm:grid-cols-2">
            <legend class="mb-3 text-xs font-medium tracking-[0.12em] text-grupo uppercase">Acceso</legend>
            <div class="sm:col-span-2">
                <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" ayuda="Úsalo para ingresar y recuperar tu contraseña." required />
            </div>
            <x-portal.campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="new-password" ayuda="Al menos 8 caracteres, con letras y números." required />
            <x-portal.campo nombre="password_confirmation" etiqueta="Repite la contraseña" tipo="password" autocomplete="new-password" required />
        </fieldset>

        <div class="flex flex-col gap-1.5">
            <label class="flex items-start gap-2.5 text-[14.5px] text-secundario">
                <input type="checkbox" wire:model="acepta_tratamiento" class="casilla mt-0.5"
                    @error('acepta_tratamiento') aria-invalid="true" aria-describedby="campo-acepta-tratamiento-error" @enderror>
                <span>{{ $consentimiento }}</span>
            </label>
            @error('acepta_tratamiento')
                <p id="campo-acepta-tratamiento-error" class="text-[13px] font-medium text-peligro">{{ $message }}</p>
            @enderror
        </div>

        <x-portal.boton accion="registrarse" class="w-full" tamano="grande">Crear mi cuenta</x-portal.boton>
    </form>

    <x-slot:pie>
        <span>¿Ya tienes cuenta?</span>
        <a href="{{ route('ingresar') }}" class="enlace font-semibold">Ingresa aquí</a>
    </x-slot:pie>
</x-portal.acceso>
