@use('App\Modules\Docencia\Filament\Docente\Pages\PreActaDelGrupo')
<x-filament-panels::page>
    @if ($guardadas)
        <x-filament::callout color="success" icon="heroicon-o-check-circle" :heading="$guardadas" />
    @endif
    @error('notas')
        <x-filament::callout color="danger" icon="heroicon-o-exclamation-triangle" :heading="$message" />
    @enderror

    @if ($esquema === null)
        <x-filament::empty-state heading="El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre." icon="heroicon-o-clipboard-document-list" />
    @elseif ($estudiantes === [])
        <x-filament::empty-state heading="El grupo no tiene estudiantes matriculados." icon="heroicon-o-user-group" />
    @else
        <x-filament::section heading="Camino al acta final" description="Las notas guardadas aquí forman el acta; la validas en la pestaña Acta final dentro de las fechas del periodo.">
            <x-ciln.pasos :pasos="$etapas" etiqueta="Etapas del acta final" />
            @if ($etapas[1][3] === 'actual')
                <div class="mt-4">
                    <x-filament::button tag="a" :href="route('filament.docente.pages.grupos.acta', $this->grupoId)" color="gray">Revisar el acta final</x-filament::button>
                </div>
            @endif
        </x-filament::section>

        <form wire:submit="guardar" novalidate>
            <x-filament::section heading="Notas parciales" :description="$esquema->nombre.' · notas de 0 a '.PreActaDelGrupo::numero($esquema->notaMaxima).', con hasta dos decimales. Deja en blanco lo que aún no evalúas.'">
                <div class="flex flex-col gap-4">
                    <ul role="list" class="grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(180px,1fr))]">
                        @foreach ($esquema->componentes as $componente)
                            @php($llenas = $registradas[$componente->id] ?? 0)
                            <li class="flex flex-col gap-2 rounded-interna bg-lienzo px-3.5 py-3">
                                <span class="flex items-baseline justify-between gap-2 text-sm">
                                    <span class="text-tinta">{{ $componente->nombre }} · {{ PreActaDelGrupo::numero($componente->peso) }} %</span>
                                    <span class="text-terciario tabular-nums">{{ $llenas }} de {{ count($estudiantes) }}</span>
                                </span>
                                <span aria-hidden="true" class="h-1.5 overflow-hidden rounded-full bg-piedra-200">
                                    <span @class(['block h-full rounded-full', 'bg-positivo' => $llenas >= count($estudiantes), 'bg-oro-400' => $llenas < count($estudiantes)]) style="width: {{ intdiv($llenas * 100, max(1, count($estudiantes))) }}%"></span>
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="-mx-2 overflow-x-auto">
                        <table class="tabla-reporte min-w-[560px]">
                            <thead>
                                <tr>
                                    <th scope="col">Estudiante</th>
                                    @foreach ($esquema->componentes as $componente)
                                        <th scope="col" class="w-32 text-right">{{ $componente->nombre }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($estudiantes as $estudiante)
                                    <tr wire:key="estudiante-{{ $estudiante->matriculaId }}">
                                        <th scope="row" class="font-normal"><span class="font-medium text-tinta">{{ $estudiante->nombreCompleto }}</span></th>
                                        @foreach ($esquema->componentes as $componente)
                                            <td>
                                                <x-filament::input.wrapper class="ml-auto w-[92px]">
                                                    <x-filament::input type="text" inputmode="decimal" placeholder="—" class="text-right" wire:model="notas.{{ $estudiante->matriculaId }}.{{ $componente->id }}" aria-label="{{ $componente->nombre }} de {{ $estudiante->nombreCompleto }}" />
                                                </x-filament::input.wrapper>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <x-filament::button type="submit">Guardar notas</x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </form>
    @endif
</x-filament-panels::page>
