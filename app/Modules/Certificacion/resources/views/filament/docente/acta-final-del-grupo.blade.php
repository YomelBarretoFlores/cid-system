@use('App\Modules\Academico\Enums\GrupoDeComponente')
@use('App\Modules\Certificacion\Filament\Docente\Pages\ActaFinalDelGrupo')
@use('App\Shared\Ui\Formato')
<x-filament-panels::page>
    @if ($aviso)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$aviso" />
    @endif
    @error('acta')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    @if ($problema)
        <x-filament::empty-state :heading="$problema" icon="heroicon-o-clipboard-document-list" />
    @else
        @php($resumen = ActaFinalDelGrupo::resumen($acta->filas))
        <x-filament::section>
            <x-ciln.pasos :pasos="$etapas" etiqueta="Etapas del acta final" class="mb-4" />
            @if ($acta->estaCerrada())
                <p class="flex flex-wrap items-center gap-2.5 text-sm">
                    <x-filament::badge color="success">Cerrada</x-filament::badge>
                    <span class="text-secundario">Validada el {{ Formato::fechaCorta($acta->validadaEn) }}. Para corregirla, el administrador debe autorizar una rectificación.</span>
                </p>
            @elseif ($editable)
                <div class="flex flex-col gap-1.5 text-sm">
                    <p class="flex flex-wrap items-center gap-2.5">
                        <x-filament::badge color="warning">En rectificación</x-filament::badge>
                        <span class="text-secundario">Corrige las notas, guárdalas y vuelve a validar el acta para cerrarla.</span>
                    </p>
                    @if ($motivo)
                        <p class="text-terciario">Motivo autorizado: {{ $motivo }}</p>
                    @endif
                </div>
            @else
                <div class="flex flex-col gap-1.5 text-sm">
                    <p class="flex flex-wrap items-center gap-2.5">
                        <x-filament::badge color="info">Vista previa</x-filament::badge>
                        <span class="text-secundario">Se calcula con las notas de la pre-acta. Al validarla, el acta se cierra y solo cambia con autorización del administrador.</span>
                    </p>
                    <p class="text-terciario">
                        @if ($curso->actasInicio && $curso->actasFin)
                            Fechas para validar: del {{ Formato::fechaCorta($curso->actasInicio) }} al {{ Formato::fechaCorta($curso->actasFin) }}.
                        @else
                            El periodo aún no tiene fechas para actas finales.
                        @endif
                    </p>
                </div>
            @endif
        </x-filament::section>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-ciln.cifra titulo="Aprobados" :valor="$resumen['aprobados']" :detalle="'de '.count($acta->filas).' estudiantes'" marca />
            <x-ciln.cifra titulo="Desaprobados" :valor="$resumen['desaprobados']" :detalle="'Nota mínima '.Formato::nota($acta->notaMinimaAprobatoria)" />
            <x-ciln.cifra titulo="NSP" :valor="$resumen['nsp']" detalle="Sin ninguna nota registrada" tono="oro" />
        </div>

        <form wire:submit="guardar" novalidate>
            <x-filament::section heading="Calificaciones" :description="$acta->esquema.' · notas de 0 a '.Formato::nota($acta->notaMaxima).'; el promedio final es la media del promedio de habilidades y del promedio de exámenes, redondeada al entero.'">
                <div class="flex flex-col gap-4">
                    <dl class="grid gap-2.5 text-sm sm:grid-cols-4">
                        <div><dt class="text-apagado">Idioma</dt><dd class="text-tinta">{{ $acta->idioma }}</dd></div>
                        <div><dt class="text-apagado">Nivel y ciclo</dt><dd class="text-tinta">{{ $acta->nivel->etiqueta() }} · {{ $acta->cicloNombre }}</dd></div>
                        <div><dt class="text-apagado">Periodo académico</dt><dd class="text-tinta">{{ $acta->periodo }}</dd></div>
                        <div><dt class="text-apagado">Docente</dt><dd class="text-tinta">{{ $acta->docente }} <span class="text-apagado">({{ $acta->docenteCodigo() }})</span></dd></div>
                    </dl>

                    <div class="-mx-2 overflow-x-auto">
                        <table class="tabla-reporte min-w-[720px]">
                            <thead>
                                <tr>
                                    <th scope="col" rowspan="2">Estudiante</th>
                                    @foreach (GrupoDeComponente::cases() as $grupo)
                                        <th scope="colgroup" class="text-center" colspan="{{ count($acta->componentesDe($grupo)) + 1 }}">{{ $grupo->etiqueta() }}</th>
                                    @endforeach
                                    <th scope="col" class="text-right" rowspan="2">Promedio</th>
                                    <th scope="col" rowspan="2">Estado final</th>
                                </tr>
                                <tr>
                                    @foreach (GrupoDeComponente::cases() as $grupo)
                                        @foreach ($acta->componentesDe($grupo) as $componente)
                                            <th scope="col" class="text-right">{{ $componente->nombre }}</th>
                                        @endforeach
                                        <th scope="col" class="text-right">Prom. {{ mb_strtolower($grupo->etiqueta()) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($acta->filas as $fila)
                                    <tr wire:key="fila-{{ $fila->matriculaId }}">
                                        <th scope="row" class="font-normal">
                                            <span class="block font-medium text-tinta">{{ $fila->estudiante }}</span>
                                            <span class="text-[13px] text-apagado">{{ $fila->documento }}</span>
                                        </th>
                                        @foreach (GrupoDeComponente::cases() as $grupo)
                                            @foreach ($acta->componentesDe($grupo) as $componente)
                                                <td class="text-right">
                                                    @if ($editable)
                                                        <x-filament::input.wrapper class="ml-auto w-[92px]">
                                                            <x-filament::input type="text" inputmode="decimal" placeholder="—" class="text-right" wire:model="notas.{{ $fila->matriculaId }}.{{ $componente->id }}" aria-label="{{ $componente->nombre }} de {{ $fila->estudiante }}" />
                                                        </x-filament::input.wrapper>
                                                    @else
                                                        {{ Formato::nota($fila->notas[$componente->id] ?? null) }}
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-right">{{ $fila->promedioDe($grupo) ?? '—' }}</td>
                                        @endforeach
                                        <td class="text-right font-semibold">{{ $fila->promedio ?? '—' }}</td>
                                        <td class="whitespace-nowrap">{{ $fila->estadoFinal->etiqueta() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($editable)
                        <div>
                            <x-filament::button type="submit" color="gray">Guardar notas</x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </form>
    @endif
</x-filament-panels::page>
