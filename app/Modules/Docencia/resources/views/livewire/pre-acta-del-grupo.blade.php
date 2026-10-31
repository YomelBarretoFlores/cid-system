@use('App\Modules\Docencia\Livewire\PreActaDelGrupo')
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Pre-acta del grupo {{ $grupo->codigo }}" :volver="route('filament.docente.pages.mis-grupos')">
        <x-slot:inicio><x-ciln.hoja-bandera :idioma="$grupo->idioma" size="40" class="max-sm:hidden" /></x-slot:inicio>
        {{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }} · {{ $grupo->periodo }}
        <x-slot:acciones>
            <x-portal.pestanas etiqueta="Registro del grupo" :enlaces="[[route('docente.asistencia', $grupo->grupoId), 'Asistencia', false], [route('docente.notas', $grupo->grupoId), 'Pre-acta', true], [route('docente.acta', $grupo->grupoId), 'Acta final', false]]" />
        </x-slot:acciones>
    </x-portal.encabezado>

    @if ($guardadas)
        <x-portal.alerta tipo="exito">{{ $guardadas }}</x-portal.alerta>
    @endif
    @error('notas')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    @if ($esquema === null)
        <x-portal.tarjeta>
            <x-portal.vacio titulo="El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre." icono="heroicon-o-clipboard-document-list" />
        </x-portal.tarjeta>
    @elseif ($estudiantes === [])
        <x-portal.tarjeta>
            <x-portal.vacio titulo="El grupo no tiene estudiantes matriculados." icono="heroicon-o-user-group" />
        </x-portal.tarjeta>
    @else
        <form wire:submit="guardar" novalidate>
            <x-portal.tarjeta titulo="Notas parciales" :descripcion="$esquema->nombre.' · notas de 0 a '.PreActaDelGrupo::numero($esquema->notaMaxima).', con hasta dos decimales. Deja en blanco lo que aún no evalúas.'">
                <x-slot:acciones>
                    <span class="flex flex-wrap gap-1.5">
                        @foreach ($esquema->componentes as $componente)
                            <span class="rounded-pildora bg-acento-claro px-3 py-1.5 text-[13.5px] font-medium">{{ $componente->nombre }} · {{ PreActaDelGrupo::numero($componente->peso) }} %</span>
                        @endforeach
                    </span>
                </x-slot:acciones>

                <div class="-mx-2 overflow-x-auto">
                    <table class="w-full min-w-[560px] border-separate border-spacing-y-0.5 text-left">
                        <thead class="text-[13px] text-apagado">
                            <tr>
                                <th scope="col" class="px-3 pb-2 font-normal">Estudiante</th>
                                @foreach ($esquema->componentes as $componente)
                                    <th scope="col" class="w-32 px-3 pb-2 font-normal">{{ $componente->nombre }} <span class="sr-only">({{ PreActaDelGrupo::numero($componente->peso) }} %)</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estudiantes as $estudiante)
                                <tr wire:key="estudiante-{{ $estudiante->matriculaId }}" class="hover:bg-fila-suave">
                                    <th scope="row" class="rounded-l-xl px-3 py-[7px] font-medium">{{ $estudiante->nombreCompleto }}</th>
                                    @foreach ($esquema->componentes as $componente)
                                        <td @class(['px-3 py-[7px]', 'rounded-r-xl' => $loop->last])>
                                            <input type="text" inputmode="decimal" placeholder="—" wire:model="notas.{{ $estudiante->matriculaId }}.{{ $componente->id }}"
                                                aria-label="{{ $componente->nombre }} de {{ $estudiante->nombreCompleto }}"
                                                class="control w-[92px] rounded-xl px-3 py-2.5 text-right font-medium">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-separador pt-3">
                    <x-portal.boton accion="guardar">Guardar notas</x-portal.boton>
                </div>
            </x-portal.tarjeta>
        </form>
    @endif
</div>
