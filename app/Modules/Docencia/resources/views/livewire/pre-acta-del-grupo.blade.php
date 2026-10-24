@use('App\Modules\Docencia\Livewire\PreActaDelGrupo')
<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <a href="{{ route('docente.grupos') }}" class="text-sm font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900">Volver a mis grupos</a>
        <h1 class="text-2xl font-semibold text-ciln-900">Pre-acta del grupo {{ $grupo->codigo }}</h1>
        <p class="text-sm text-slate-600">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }} · {{ $grupo->periodo }}</p>
    </header>

    @if ($guardadas)
        <x-portal.alerta tipo="exito">{{ $guardadas }}</x-portal.alerta>
    @endif
    @error('notas')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    @if ($esquema === null)
        <x-portal.alerta>El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.</x-portal.alerta>
    @elseif ($estudiantes === [])
        <x-portal.alerta>El grupo no tiene estudiantes matriculados.</x-portal.alerta>
    @else
        <form wire:submit="guardar" class="flex flex-col gap-4" novalidate>
            <p class="text-sm text-slate-600">{{ $esquema->nombre }} · notas de 0 a {{ PreActaDelGrupo::numero($esquema->notaMaxima) }}, con hasta dos decimales. Deja en blanco lo que aún no evalúas.</p>

            <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-800">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Estudiante</th>
                            @foreach ($esquema->componentes as $componente)
                                <th scope="col" class="px-4 py-3 font-semibold">{{ $componente->nombre }} <span class="font-normal text-slate-600">({{ PreActaDelGrupo::numero($componente->peso) }} %)</span></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($estudiantes as $estudiante)
                            <tr wire:key="estudiante-{{ $estudiante->matriculaId }}">
                                <th scope="row" class="px-4 py-2 text-left font-medium text-slate-900">{{ $estudiante->nombreCompleto }}</th>
                                @foreach ($esquema->componentes as $componente)
                                    <td class="px-4 py-2">
                                        <input type="text" inputmode="decimal" wire:model="notas.{{ $estudiante->matriculaId }}.{{ $componente->id }}"
                                            aria-label="{{ $componente->nombre }} de {{ $estudiante->nombreCompleto }}"
                                            class="block w-20 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-base text-slate-900 shadow-xs focus:outline-2 focus:outline-ciln-600 sm:text-sm">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-portal.boton accion="guardar" class="self-start">Guardar notas</x-portal.boton>
        </form>
    @endif
</div>
