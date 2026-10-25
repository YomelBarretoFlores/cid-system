@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('App\Modules\Certificacion\Livewire\ActaFinalDelGrupo')
@use('App\Shared\Ui\Formato')
@php
    $colores = [EstadoFinal::Aprobado->value => 'success', EstadoFinal::Desaprobado->value => 'danger', EstadoFinal::Nsp->value => 'gray'];
    $editable = $acta instanceof \App\Modules\Certificacion\Data\ActaDelGrupo && $acta->admiteRectificacion();
@endphp
<div class="flex flex-col gap-2 sm:gap-3.5">
    <x-portal.encabezado titulo="Acta final del grupo {{ $curso->codigo }}" :volver="route('docente.grupos')">
        <x-slot:inicio><x-ciln.hoja-bandera :idioma="$curso->idioma" size="40" class="max-sm:hidden" /></x-slot:inicio>
        {{ $curso->idioma }} · Ciclo {{ $curso->cicloNumero }} · {{ $curso->nivel->etiqueta() }} · {{ $curso->periodo }}
        <x-slot:acciones>
            <x-portal.pestanas etiqueta="Registro del grupo" :enlaces="[[route('docente.asistencia', $curso->grupoId), 'Asistencia', false], [route('docente.notas', $curso->grupoId), 'Pre-acta', false], [route('docente.acta', $curso->grupoId), 'Acta final', true]]" />
        </x-slot:acciones>
    </x-portal.encabezado>

    @if ($aviso)
        <x-portal.alerta tipo="exito">{{ $aviso }}</x-portal.alerta>
    @endif
    @error('acta')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    @if ($problema)
        <x-portal.tarjeta>
            <x-portal.vacio :titulo="$problema" icono="heroicon-o-clipboard-document-list" />
        </x-portal.tarjeta>
    @else
        @php($resumen = ActaFinalDelGrupo::resumen($acta->filas))
        <div class="grid gap-2 sm:grid-cols-3 sm:gap-3.5">
            <x-ciln.cifra titulo="Aprobados" :valor="$resumen['aprobados']" :detalle="'de '.count($acta->filas).' estudiantes'" marca />
            <x-ciln.cifra titulo="Desaprobados" :valor="$resumen['desaprobados']" :detalle="'Nota mínima '.Formato::nota($acta->notaMinimaAprobatoria)" />
            <x-ciln.cifra titulo="NSP" :valor="$resumen['nsp']" detalle="Sin ninguna nota registrada" tono="oro" />
        </div>

        <x-portal.tarjeta>
            @if ($acta->estaCerrada())
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="flex flex-wrap items-center gap-2.5">
                        <x-portal.insignia color="success">Cerrada</x-portal.insignia>
                        <span class="text-secundario">Validada el {{ Formato::fechaCorta($acta->validadaEn) }}. Para corregirla, el administrador debe autorizar una rectificación.</span>
                    </p>
                    <x-portal.boton variante="secundario" tamano="compacto" :href="route('docente.acta.pdf', $curso->grupoId)">
                        <x-heroicon-o-arrow-down-tray aria-hidden="true" class="size-4" /> Descargar acta (PDF)
                    </x-portal.boton>
                </div>
            @elseif ($editable)
                <div class="flex flex-col gap-1.5">
                    <p class="flex flex-wrap items-center gap-2.5">
                        <x-portal.insignia color="warning">En rectificación</x-portal.insignia>
                        <span class="text-secundario">Corrige las notas, guárdalas y vuelve a validar el acta para cerrarla.</span>
                    </p>
                    @if ($motivo)
                        <p class="text-sm text-terciario">Motivo autorizado: {{ $motivo }}</p>
                    @endif
                </div>
            @else
                <div class="flex flex-col gap-1.5">
                    <p class="flex flex-wrap items-center gap-2.5">
                        <x-portal.insignia color="info">Vista previa</x-portal.insignia>
                        <span class="text-secundario">Se calcula con las notas de la pre-acta. Al validarla, el acta se cierra y solo cambia con autorización del administrador.</span>
                    </p>
                    <p class="text-sm text-terciario">
                        @if ($curso->actasInicio && $curso->actasFin)
                            Fechas para validar: del {{ Formato::fechaCorta($curso->actasInicio) }} al {{ Formato::fechaCorta($curso->actasFin) }}.
                        @else
                            El periodo aún no tiene fechas para actas finales.
                        @endif
                    </p>
                </div>
            @endif
        </x-portal.tarjeta>

        <form wire:submit="guardar" novalidate>
            <x-portal.tarjeta titulo="Calificaciones" :descripcion="$acta->esquema.' · notas de 0 a '.Formato::nota($acta->notaMaxima).'; el promedio pondera cada componente por su peso.'">
                <x-slot:acciones>
                    <span class="flex flex-wrap gap-1.5">
                        @foreach ($acta->componentes as $componente)
                            <span class="rounded-pildora bg-acento-claro px-3 py-1.5 text-[13.5px] font-medium">{{ $componente->nombre }} · {{ Formato::nota($componente->peso) }} %</span>
                        @endforeach
                    </span>
                </x-slot:acciones>

                <dl class="grid gap-2.5 text-sm sm:grid-cols-4">
                    <x-portal.dato etiqueta="Idioma">{{ $acta->idioma }}</x-portal.dato>
                    <x-portal.dato etiqueta="Nivel y ciclo">{{ $acta->nivel->etiqueta() }} · {{ $acta->cicloNombre }}</x-portal.dato>
                    <x-portal.dato etiqueta="Periodo académico">{{ $acta->periodo }}</x-portal.dato>
                    <x-portal.dato etiqueta="Docente">{{ $acta->docente }} <span class="text-apagado">(ID {{ $acta->docenteId }})</span></x-portal.dato>
                </dl>

                <div class="-mx-2 overflow-x-auto">
                    <table class="w-full min-w-[640px] border-separate border-spacing-y-0.5 text-left">
                        <thead class="text-[13px] text-apagado">
                            <tr>
                                <th scope="col" class="px-3 pb-2 font-normal">Estudiante</th>
                                @foreach ($acta->componentes as $componente)
                                    <th scope="col" class="w-28 px-3 pb-2 text-right font-normal">{{ $componente->nombre }}</th>
                                @endforeach
                                <th scope="col" class="w-24 px-3 pb-2 text-right font-normal">Promedio</th>
                                <th scope="col" class="w-32 px-3 pb-2 font-normal">Estado final</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($acta->filas as $fila)
                                <tr wire:key="fila-{{ $fila->matriculaId }}" class="hover:bg-fila-suave">
                                    <th scope="row" class="rounded-l-xl px-3 py-[7px] font-normal">
                                        <span class="block font-medium">{{ $fila->estudiante }}</span>
                                        <span class="text-[13px] text-apagado">{{ $fila->documento }}</span>
                                    </th>
                                    @foreach ($acta->componentes as $componente)
                                        <td class="px-3 py-[7px] text-right">
                                            @if ($editable)
                                                <input type="text" inputmode="decimal" placeholder="—" wire:model="notas.{{ $fila->matriculaId }}.{{ $componente->id }}"
                                                    aria-label="{{ $componente->nombre }} de {{ $fila->estudiante }}"
                                                    class="control w-[84px] rounded-xl px-3 py-2 text-right font-medium">
                                            @else
                                                {{ Formato::nota($fila->notas[$componente->id] ?? null) }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-[7px] text-right font-semibold">{{ $fila->promedio ?? '—' }}</td>
                                    <td class="rounded-r-xl px-3 py-[7px]"><x-portal.insignia :color="$colores[$fila->estadoFinal->value]">{{ $fila->estadoFinal->etiqueta() }}</x-portal.insignia></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($acta->estaCerrada())
                    <div x-data="{ confirmar: false }" class="flex flex-wrap items-center justify-end gap-2 border-t border-separador pt-3">
                        @if ($editable)
                            <x-portal.boton variante="secundario" accion="guardar">Guardar notas</x-portal.boton>
                        @endif
                        <template x-if="! confirmar">
                            <x-portal.boton type="button" x-on:click="confirmar = true" :disabled="! $editable && ! $enFechas">Validar y cerrar acta</x-portal.boton>
                        </template>
                        <template x-if="confirmar">
                            <span class="flex flex-wrap items-center gap-2" role="group" aria-label="Confirmar el cierre del acta">
                                <span class="text-sm text-secundario">Al cerrarla ya no podrás cambiar las notas.</span>
                                <x-portal.boton variante="secundario" type="button" x-on:click="confirmar = false">Cancelar</x-portal.boton>
                                <x-portal.boton type="button" wire:click="validar" accion="validar">Sí, cerrar acta</x-portal.boton>
                            </span>
                        </template>
                    </div>
                @endunless
            </x-portal.tarjeta>
        </form>
    @endif
</div>
