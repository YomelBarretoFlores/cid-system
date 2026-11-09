@use('App\Shared\Auth\Enums\TemaDeInterfaz')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Preferencias">Configura cómo se ve tu portal del CILN.</x-portal.encabezado>

    <x-portal.tarjeta titulo="Modo oscuro" class="max-w-[760px]">
        <form wire:submit="guardar" class="flex flex-col gap-4" novalidate>
            <fieldset class="flex flex-col gap-2" @error('tema') aria-describedby="error-tema" @enderror>
                <legend class="sr-only">Tema del portal</legend>
                @foreach ($opciones as $opcion)
                    <label class="flex cursor-pointer items-center gap-3.5 rounded-interna border border-borde-interno px-4 py-3 hover:bg-hover-suave has-checked:border-acento">
                        <input type="radio" name="tema" value="{{ $opcion->value }}" wire:model="tema" class="casilla" @checked($tema === $opcion->value)>
                        <span aria-hidden="true" class="flex h-9 w-14 shrink-0 overflow-hidden rounded-control border border-borde">
                            @foreach (match ($opcion) { TemaDeInterfaz::Claro => ['muestra-claro'], TemaDeInterfaz::Oscuro => ['muestra-oscuro'], TemaDeInterfaz::Sistema => ['muestra-claro', 'muestra-oscuro'] } as $muestra)
                                <span class="{{ $muestra }} flex flex-1 flex-col gap-1 bg-fondo p-1.5"><span class="h-1.5 w-full rounded-[2px] bg-tarjeta"></span><span class="h-1 w-3/4 rounded-[2px] bg-tinta"></span></span>
                            @endforeach
                        </span>
                        <span class="font-medium">{{ $opcion->etiqueta() }}</span>
                    </label>
                @endforeach
            </fieldset>
            @error('tema')
                <p id="error-tema" class="text-[14px] text-peligro">{{ $message }}</p>
            @enderror
            <x-portal.boton class="self-start" accion="guardar">Guardar cambios</x-portal.boton>
        </form>
    </x-portal.tarjeta>
</div>
