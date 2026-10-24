<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <a href="{{ route('docente.grupos') }}" class="text-sm font-medium text-ciln-700 underline underline-offset-2 hover:text-ciln-900">Volver a mis grupos</a>
        <h1 class="text-2xl font-semibold text-ciln-900">Asistencia del grupo {{ $grupo->codigo }}</h1>
        <p class="text-sm text-slate-600">{{ $grupo->idioma }} · Ciclo {{ $grupo->cicloNumero }} · {{ $grupo->nivel->etiqueta() }} · {{ $grupo->periodo }}</p>
    </header>

    @if ($guardada)
        <x-portal.alerta tipo="exito">{{ $guardada }}</x-portal.alerta>
    @endif
    @error('asistencia')
        <x-portal.alerta tipo="error">{{ $message }}</x-portal.alerta>
    @enderror

    @if ($clases === [])
        <x-portal.alerta>Este grupo todavía no tiene clases para registrar.</x-portal.alerta>
    @else
        <form wire:submit="guardar" class="flex flex-col gap-6" novalidate>
            <div class="flex flex-col gap-1.5">
                <label for="campo-clase" class="text-sm font-medium text-slate-800">Clase</label>
                <select id="campo-clase" wire:model.live="clase" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 shadow-xs focus:outline-2 focus:outline-ciln-600 sm:text-sm">
                    @foreach ($clases as $opcion)
                        <option value="{{ $opcion->clave() }}">{{ $opcion->descripcion() }}{{ isset($registradas[$opcion->clave()]) ? ' · registrada' : '' }}</option>
                    @endforeach
                </select>
            </div>

            @if ($estudiantes === [])
                <x-portal.alerta>El grupo no tiene estudiantes matriculados.</x-portal.alerta>
            @else
                <ul role="list" class="flex flex-col divide-y divide-slate-200 rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    @foreach ($estudiantes as $estudiante)
                        <li wire:key="estudiante-{{ $estudiante->matriculaId }}" class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <span id="estudiante-{{ $estudiante->matriculaId }}" class="text-sm font-medium text-slate-900">{{ $estudiante->nombreCompleto }}</span>
                            <div role="radiogroup" aria-labelledby="estudiante-{{ $estudiante->matriculaId }}" class="flex flex-wrap gap-4 text-sm">
                                @foreach ($opciones as $opcion)
                                    <label class="inline-flex items-center gap-1.5">
                                        <input type="radio" wire:model="estados.{{ $estudiante->matriculaId }}" value="{{ $opcion->value }}" class="size-4 accent-ciln-700">
                                        {{ $opcion->etiqueta() }}
                                    </label>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ul>

                <x-portal.boton accion="guardar" class="self-start">Guardar asistencia</x-portal.boton>
            @endif
        </form>
    @endif
</div>
