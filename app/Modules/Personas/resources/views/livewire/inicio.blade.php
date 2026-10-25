<div class="flex flex-col gap-2 sm:gap-3.5">
    <livewire:matricula.resumen-del-estudiante :es-docente="$esDocente" />

    @if ($esDocente)
        <a href="{{ route('docente.grupos') }}" class="group flex items-center gap-4 rounded-tarjeta bg-tarjeta p-6 transition-shadow hover:shadow-[inset_0_0_0_2px_var(--color-oro-300)] focus-visible:shadow-foco focus-visible:outline-none">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-acento-claro">
                <x-heroicon-o-user-group aria-hidden="true" class="size-[22px] text-oro-600" />
            </span>
            <span class="flex flex-1 flex-col">
                <span class="text-[17px] font-semibold">Mis grupos</span>
                <span class="text-[14.5px] text-terciario">Registra la asistencia y las notas parciales de tus grupos.</span>
            </span>
            <x-heroicon-o-arrow-right aria-hidden="true" class="size-4 text-apagado transition-transform group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
