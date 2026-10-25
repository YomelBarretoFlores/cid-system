<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Matrícula">Elige un grupo del ciclo que te corresponde y el pago de matrícula validado que usarás.</x-portal.encabezado>

    @error('matricula')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    <form wire:submit="matricularme" class="columnas" novalidate>
        <x-portal.tarjeta titulo="Grupos disponibles" descripcion="Las vacantes se actualizan automáticamente cada 15 segundos." wire:poll.15s.visible>
            @forelse ($ofertaPorIdioma as $idioma => $grupos)
                <fieldset wire:key="idioma-{{ $idioma }}" class="flex flex-col gap-2.5" @error('grupo') aria-describedby="campo-grupo-error" @enderror>
                    <legend class="mb-2.5 flex items-center gap-2.5 font-semibold">
                        <x-ciln.hoja-bandera :idioma="$idioma" size="30" />{{ $idioma }}
                    </legend>

                    @foreach ($grupos as $grupo)
                        @php($sinVacantes = $grupo->vacantesDisponibles < 1)
                        <label wire:key="grupo-{{ $grupo->id }}" @class([
                            'grid grid-cols-[22px_minmax(0,1fr)_auto] items-center gap-3.5 rounded-[18px] p-[18px] shadow-[inset_0_0_0_1px_var(--color-borde-interno)] has-checked:bg-acento-claro has-checked:shadow-[inset_0_0_0_2px_var(--color-oro-400)] has-focus-visible:shadow-foco',
                            'cursor-pointer bg-tarjeta-interna hover:bg-fila-hover' => ! $sinVacantes,
                            'cursor-not-allowed bg-tarjeta-interna opacity-60' => $sinVacantes,
                        ])>
                            <input type="radio" name="grupo" value="{{ $grupo->id }}" wire:model="grupo" @disabled($sinVacantes)
                                aria-describedby="grupo-{{ $grupo->id }}-detalle grupo-{{ $grupo->id }}-vacantes"
                                class="casilla size-5 focus:outline-none">
                            <span id="grupo-{{ $grupo->id }}-detalle" class="flex flex-col gap-0.5">
                                <span class="text-base font-semibold">Grupo {{ $grupo->codigo }}</span>
                                <span class="text-[13.5px] text-terciario">Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }} · Periodo {{ $grupo->periodo }}</span>
                                @forelse ($grupo->horarios as $horario)
                                    <span class="text-[13.5px]">{{ $horario }}</span>
                                @empty
                                    <span class="text-[13.5px]">Horario por confirmar</span>
                                @endforelse
                                <span class="text-[13.5px] text-terciario">{{ $grupo->aula !== null ? "Aula {$grupo->aula}" : ($grupo->enlaceVirtual !== null ? 'Clases virtuales' : 'Aula por asignar') }}</span>
                            </span>
                            <span id="grupo-{{ $grupo->id }}-vacantes" aria-live="polite" aria-atomic="true">
                                <x-portal.insignia :color="$sinVacantes ? 'danger' : ($grupo->vacantesDisponibles <= 3 ? 'warning' : 'success')">
                                    {{ match (true) { $sinVacantes => 'Sin vacantes', $grupo->vacantesDisponibles === 1 => '1 vacante', default => "{$grupo->vacantesDisponibles} vacantes" } }}
                                </x-portal.insignia>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            @empty
                <x-portal.vacio titulo="Por ahora no hay grupos abiertos para tu ciclo." icono="heroicon-o-calendar-days">
                    Vuelve a revisar durante el periodo de matrícula.
                </x-portal.vacio>
            @endforelse

            @error('grupo')
                <p id="campo-grupo-error" class="text-[13px] font-medium text-peligro">{{ $message }}</p>
            @enderror
        </x-portal.tarjeta>

        <x-portal.tarjeta titulo="Confirmar matrícula" class="lg:sticky lg:top-[30px]">
            @if ($opcionesDePago === [])
                <x-portal.alerta tipo="aviso">
                    Aún no tienes un pago de matrícula validado.
                    <a href="{{ route('estudiante.pagos') }}" class="font-semibold underline underline-offset-2">Registra tu pago</a> y espera a que el CILN lo valide.
                </x-portal.alerta>
            @else
                <x-portal.seleccion nombre="pago" etiqueta="Pago que usarás" :opciones="$opcionesDePago" vacio="Elige tu pago" ayuda="Solo aparecen tus pagos de matrícula validados que aún no usaste." />
            @endif

            @if ($opcionesDePago !== [] && $ofertaPorIdioma->isNotEmpty())
                <x-portal.boton accion="matricularme" class="mt-1 w-full">Matricularme</x-portal.boton>
            @endif
        </x-portal.tarjeta>
    </form>
</div>
