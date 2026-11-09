<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Modificar datos" :volver="route('estudiante.inicio')">
        Actualiza cómo podemos comunicarnos contigo y cambia tu contraseña.
    </x-portal.encabezado>

    <div class="grid gap-2 sm:gap-3.5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <x-portal.tarjeta titulo="Datos personales">
            <dl class="grid gap-3 text-[14.5px] sm:grid-cols-2">
                <x-portal.dato etiqueta="Apellidos y nombres" class="sm:col-span-2">{{ mb_strtoupper(str_replace(',', '', $ficha->nombreCompleto)) }}</x-portal.dato>
                <x-portal.dato etiqueta="Documento">{{ $ficha->documento }}</x-portal.dato>
                <x-portal.dato etiqueta="Fecha de nacimiento">{{ $ficha->fechaNacimiento ?? 'Sin registrar' }}</x-portal.dato>
                <x-portal.dato etiqueta="Correo" class="break-words sm:col-span-2">{{ $ficha->correo ?? 'Sin registrar' }}</x-portal.dato>
            </dl>
            <p class="text-[13px] text-terciario">Estos datos los actualiza el CILN. Si hay un error, acércate a la oficina del CILN con tu documento.</p>
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Datos de contacto">
            <form wire:submit="guardarContacto" class="flex flex-col gap-3.5" novalidate>
                @error('contacto')
                    <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
                @enderror
                <x-portal.campo nombre="celular" etiqueta="Celular" tipo="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="Ej.: 987654321" />
                <x-portal.campo nombre="direccion" etiqueta="Dirección" autocomplete="street-address" maxlength="200" placeholder="Ej.: Jr. Los Pinos 123, Huaraz" />
                <x-portal.boton class="self-start" accion="guardarContacto">Guardar datos de contacto</x-portal.boton>
            </form>
        </x-portal.tarjeta>
    </div>

    <x-portal.tarjeta titulo="Cambiar contraseña" descripcion="Al cambiarla cerraremos tus sesiones en otros dispositivos y te avisaremos por correo.">
        <form wire:submit="cambiarContrasena" class="grid gap-3.5 md:grid-cols-3" novalidate>
            <x-portal.campo nombre="password_actual" etiqueta="Contraseña actual" tipo="password" autocomplete="current-password" required />
            <x-portal.campo nombre="password" etiqueta="Nueva contraseña" tipo="password" autocomplete="new-password" ayuda="Al menos 8 caracteres, con letras y números." required />
            <x-portal.campo nombre="password_confirmation" etiqueta="Repite la nueva contraseña" tipo="password" autocomplete="new-password" required />
            <x-portal.boton class="self-start md:col-span-3" accion="cambiarContrasena">Cambiar contraseña</x-portal.boton>
        </form>
    </x-portal.tarjeta>
</div>
