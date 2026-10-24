<x-portal.tarjeta titulo="Crea tu cuenta de estudiante" descripcion="Si ya estás registrado en el CILN, usa el mismo correo que diste en la oficina." ancho="max-w-2xl">
    <form wire:submit="registrarse" class="flex flex-col gap-6" novalidate>
        @error('registro')
            <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
        @enderror

        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend class="mb-2 text-sm font-semibold text-ciln-900">Documento de identidad</legend>
            <x-portal.seleccion nombre="tipo_documento" etiqueta="Tipo de documento" :opciones="$tiposDeDocumento" />
            <x-portal.campo nombre="numero_documento" etiqueta="Número de documento" autocomplete="off" inputmode="text" required />
        </fieldset>

        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend class="mb-2 text-sm font-semibold text-ciln-900">Datos personales</legend>
            <x-portal.campo nombre="apellido_paterno" etiqueta="Apellido paterno" autocomplete="family-name" required />
            <x-portal.campo nombre="apellido_materno" etiqueta="Apellido materno (opcional)" autocomplete="off" />
            <x-portal.campo nombre="nombres" etiqueta="Nombres" autocomplete="given-name" required />
            <x-portal.campo nombre="fecha_nacimiento" etiqueta="Fecha de nacimiento" tipo="date" autocomplete="bday" required />
            <x-portal.campo nombre="celular" etiqueta="Celular (opcional)" tipo="tel" autocomplete="tel" inputmode="tel" />
        </fieldset>

        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend class="mb-2 text-sm font-semibold text-ciln-900">Acceso</legend>
            <div class="sm:col-span-2">
                <x-portal.campo nombre="email" etiqueta="Correo" tipo="email" autocomplete="email" ayuda="Te enviaremos un enlace para verificarlo." required />
            </div>
            <x-portal.campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="new-password" ayuda="Al menos 8 caracteres, con letras y números." required />
            <x-portal.campo nombre="password_confirmation" etiqueta="Repite la contraseña" tipo="password" autocomplete="new-password" required />
        </fieldset>

        <div class="flex flex-col gap-1.5">
            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="acepta_tratamiento" class="mt-0.5 size-4 rounded border-slate-300 text-ciln-700 focus:outline-ciln-600"
                    @error('acepta_tratamiento') aria-invalid="true" aria-describedby="campo-acepta-tratamiento-error" @enderror>
                <span>Acepto que el CILN de la UNASAM trate mis datos personales para gestionar mi matrícula, pagos y certificados, conforme a la Ley N° 29733, Ley de Protección de Datos Personales.</span>
            </label>
            @error('acepta_tratamiento')
                <p id="campo-acepta-tratamiento-error" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <x-portal.boton accion="registrarse">Crear mi cuenta</x-portal.boton>
    </form>

    <p class="mt-6 text-sm text-slate-700">
        ¿Ya tienes cuenta? <a href="{{ route('estudiante.ingresar') }}" class="font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900">Ingresa aquí</a>.
    </p>
</x-portal.tarjeta>
