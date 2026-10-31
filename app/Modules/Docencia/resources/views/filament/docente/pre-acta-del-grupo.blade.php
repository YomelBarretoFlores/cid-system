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
        <form wire:submit="guardar" novalidate>
            <x-filament::section heading="Notas parciales" :description="$esquema->nombre.' · notas de 0 a '.PreActaDelGrupo::numero($esquema->notaMaxima).', con hasta dos decimales. Deja en blanco lo que aún no evalúas.'">
                <div class="flex flex-col gap-4">
                    <p class="flex flex-wrap gap-1.5">
                        @foreach ($esquema->componentes as $componente)
                            <x-filament::badge color="gray">{{ $componente->nombre }} · {{ PreActaDelGrupo::numero($componente->peso) }} %</x-filament::badge>
                        @endforeach
                    </p>

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
