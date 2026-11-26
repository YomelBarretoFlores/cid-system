<x-portal.perfil-del-estudiante :ficha="$ficha" :es-docente="$esDocente">
    <x-slot:acciones>
        @if ($ficha->habilitada)
            <x-portal.boton :href="route('estudiante.reportes')" variante="secundario" tamano="compacto" wire:navigate>Resumen académico</x-portal.boton>
        @endif
        <x-portal.boton :href="route('estudiante.perfil.datos')" variante="secundario" tamano="compacto" wire:navigate>Editar perfil</x-portal.boton>
    </x-slot:acciones>

    @if ($ficha->habilitada)
        <livewire:matricula.resumen-del-estudiante :es-docente="$esDocente" />
    @else
        <x-portal.alerta tipo="aviso">
            <p class="font-semibold">Tu cuenta aún no está habilitada.</p>
            <p class="mt-1">Registra tu pago en <a href="{{ route('estudiante.pagos') }}" wire:navigate class="font-medium underline">Registro de pagos</a> y acércate a la oficina del CILN con tu recibo y tu documento de identidad. Cuando el personal valide tu pago, verás la matrícula, los trámites y tus reportes.</p>
        </x-portal.alerta>
    @endif
</x-portal.perfil-del-estudiante>
