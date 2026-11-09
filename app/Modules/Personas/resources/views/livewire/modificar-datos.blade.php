<x-portal.perfil-del-estudiante :ficha="$ficha" :lateral="false">
    <x-slot:acciones>
        <x-portal.boton :href="route('estudiante.inicio')" variante="secundario" tamano="compacto" wire:navigate>Ver perfil</x-portal.boton>
    </x-slot:acciones>

    <div class="grid items-start gap-2 sm:gap-3.5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <x-portal.tarjeta titulo="Datos personales">
            <x-portal.alerta>Revisa tus datos de contacto y mantenlos al día: por ellos te avisamos de tus pagos, matrículas y trámites. Estos datos los actualiza el CILN: tus nombres, tu documento, tu fecha de nacimiento y tu correo; si hay un error, acércate a la oficina del CILN con tu documento.</x-portal.alerta>
            <dl class="grid gap-3 text-[14.5px] sm:grid-cols-2">
                <x-portal.dato etiqueta="Apellidos y nombres">{{ mb_strtoupper(str_replace(',', '', $ficha->nombreCompleto)) }}</x-portal.dato>
                <x-portal.dato etiqueta="Documento">{{ $ficha->documento }}</x-portal.dato>
                <x-portal.dato etiqueta="Fecha de nacimiento">{{ $ficha->fechaNacimiento ?? 'Sin registrar' }}</x-portal.dato>
                <x-portal.dato etiqueta="Correo" class="break-words">{{ $ficha->correo ?? 'Sin registrar' }}</x-portal.dato>
            </dl>
            <form wire:submit="guardarContacto" class="flex flex-col gap-3.5 border-t border-separador pt-4" novalidate>
                @error('contacto')
                    <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
                @enderror
                <x-portal.campo nombre="direccion" etiqueta="Dirección" autocomplete="street-address" maxlength="200" placeholder="Ej.: Jr. Los Pinos 123, Huaraz" />
                <div class="grid gap-3.5 sm:grid-cols-2">
                    <x-portal.campo nombre="celular" etiqueta="Celular" tipo="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="Ej.: 987654321" />
                </div>
                <x-portal.boton class="self-center" accion="guardarContacto">Guardar datos</x-portal.boton>
            </form>
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Cambio de contraseña">
            <x-portal.alerta>Ingresa primero tu contraseña actual. Luego escribe y confirma tu contraseña nueva. Cerraremos tus sesiones en otros dispositivos y te avisaremos por correo.</x-portal.alerta>
            <form wire:submit="cambiarContrasena" class="flex flex-col gap-3.5" novalidate>
                <x-portal.campo nombre="password_actual" etiqueta="Contraseña actual" tipo="password" autocomplete="current-password" required />
                <x-portal.campo nombre="password" etiqueta="Nueva contraseña" tipo="password" autocomplete="new-password" ayuda="Al menos 8 caracteres, con letras y números." required />
                <x-portal.campo nombre="password_confirmation" etiqueta="Confirma la nueva contraseña" tipo="password" autocomplete="new-password" required />
                <x-portal.boton class="self-center" accion="cambiarContrasena">Cambiar contraseña</x-portal.boton>
            </form>
        </x-portal.tarjeta>
    </div>
</x-portal.perfil-del-estudiante>
