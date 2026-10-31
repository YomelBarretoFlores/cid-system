<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Asistencia del grupo {{ $grupo->codigo }}" :volver="route('filament.docente.pages.mis-grupos')">
        <x-slot:inicio><x-ciln.hoja-bandera :idioma="$grupo->idioma" size="40" class="max-sm:hidden" /></x-slot:inicio>
        {{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }} · {{ $grupo->periodo }}
        <x-slot:acciones>
            <x-portal.pestanas etiqueta="Registro del grupo" :enlaces="[[route('docente.asistencia', $grupo->grupoId), 'Asistencia', true], [route('docente.notas', $grupo->grupoId), 'Pre-acta', false], [route('docente.acta', $grupo->grupoId), 'Acta final', false]]" />
        </x-slot:acciones>
    </x-portal.encabezado>

    @if ($guardada)
        <x-portal.alerta tipo="exito">{{ $guardada }}</x-portal.alerta>
    @endif
    @error('asistencia')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    @if ($clases === [])
        <x-portal.tarjeta>
            <x-portal.vacio titulo="Este grupo todavía no tiene clases para registrar." icono="heroicon-o-calendar-days">
                Solo se registran clases del horario entre el inicio del periodo y hoy.
            </x-portal.vacio>
        </x-portal.tarjeta>
    @else
        <form wire:submit="guardar" class="columnas" novalidate>
            <x-portal.tarjeta class="lg:sticky lg:top-[30px] lg:order-last">
                <x-portal.seleccion nombre="clase" etiqueta="Clase" :opciones="collect($clases)->mapWithKeys(fn ($opcion) => [$opcion->clave() => $opcion->descripcion().(isset($registradas[$opcion->clave()]) ? ' · registrada' : '')])"
                    ayuda="Solo clases del horario entre el inicio del periodo y hoy. Nunca una clase futura." vivo />
            </x-portal.tarjeta>

            <x-portal.tarjeta>
                @if ($estudiantes === [])
                    <x-portal.vacio titulo="El grupo no tiene estudiantes matriculados." icono="heroicon-o-user-group" />
                @else
                    <ul role="list" class="-mx-2 flex flex-col gap-1">
                        @foreach ($estudiantes as $estudiante)
                            <li wire:key="estudiante-{{ $estudiante->matriculaId }}" class="flex flex-wrap items-center gap-3 rounded-control px-3 py-2.5 hover:bg-fila-suave">
                                <span class="w-6 text-[13px] text-apagado">{{ $loop->iteration }}</span>
                                <span id="estudiante-{{ $estudiante->matriculaId }}" class="min-w-40 flex-1 font-medium">{{ $estudiante->nombreCompleto }}</span>
                                <div role="radiogroup" aria-labelledby="estudiante-{{ $estudiante->matriculaId }}" class="ml-auto flex shrink-0 gap-0.5 rounded-pildora bg-lienzo p-1">
                                    @foreach ($opciones as $opcion)
                                        <label class="cursor-pointer rounded-pildora px-3 py-[7px] text-[13.5px] font-medium text-secundario has-checked:bg-tarjeta has-checked:text-tinta has-checked:shadow-[0_1px_2px_rgb(29_27_22/0.12)] has-focus-visible:shadow-foco">
                                            <input type="radio" wire:model="estados.{{ $estudiante->matriculaId }}" value="{{ $opcion->value }}" class="sr-only">
                                            {{ $opcion->etiqueta() }}
                                        </label>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-separador pt-3">
                        <span class="text-[13.5px] text-apagado">Guardar de nuevo corrige la asistencia de esta clase.</span>
                        <x-portal.boton accion="guardar">Guardar asistencia</x-portal.boton>
                    </div>
                @endif
            </x-portal.tarjeta>
        </form>
    @endif
</div>
