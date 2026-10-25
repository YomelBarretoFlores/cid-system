{{-- Summary tables of the dashboard; expects $tablero, $soles, $horas, $porcentaje from the page view. --}}
@use('App\Modules\Tablero\Support\DiasDeClase')
<div class="grid gap-3.5 lg:grid-cols-2">
    <x-ciln.tabla titulo="Recaudación por idioma y nivel" :columnas="['Idioma', 'Nivel', 'Pagos', 'Monto']" :alineadas="[2, 3]">
        @forelse ($tablero->recaudacion->filas as $fila)
            <tr wire:key="recaudacion-{{ $fila->idioma }}-{{ $fila->nivel }}" class="border-b border-separador">
                <td class="py-2.5 pr-3 font-medium">{{ $fila->idioma }}</td>
                <td class="py-2.5 pr-3 text-terciario">{{ $fila->nivel }}</td>
                <td class="py-2.5 pr-3 text-right text-apagado">{{ $fila->operaciones }}</td>
                <td class="py-2.5 text-right font-semibold whitespace-nowrap">{{ $soles($fila->centimos) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="py-3 text-apagado">No hay pagos validados en este periodo.</td></tr>
        @endforelse
    </x-ciln.tabla>

    <x-ciln.tabla titulo="Deserción por nivel y por idioma" :columnas="['', 'Matrículas', 'Retiros', 'Tasa']" :alineadas="[1, 2, 3]">
        @foreach (['Por nivel' => $tablero->desercion->porNivel, 'Por idioma' => $tablero->desercion->porIdioma, '' => [$tablero->desercion->total]] as $grupo => $filas)
            @if ($grupo !== '')
                <tr><th scope="rowgroup" colspan="4" class="pt-3 pb-1 text-[13px] font-normal text-apagado">{{ $grupo }}</th></tr>
            @endif
            @foreach ($filas as $fila)
                <tr wire:key="desercion-{{ $grupo }}-{{ $loop->index }}" @class(['border-b border-separador', 'font-semibold' => $grupo === ''])>
                    <td class="py-2.5 pr-3 font-medium">{{ $fila->etiqueta }}</td>
                    <td class="py-2.5 pr-3 text-right text-apagado">{{ $fila->matriculas }}</td>
                    <td class="py-2.5 pr-3 text-right text-apagado">{{ $fila->retiradas }}</td>
                    <td class="py-2.5 text-right">{{ $porcentaje($fila->tasa()) }}</td>
                </tr>
            @endforeach
        @endforeach
    </x-ciln.tabla>
</div>

<x-ciln.tabla titulo="Demanda horaria" :columnas="['Horario', 'Días', 'Grupos', 'Matriculados', 'Vacantes', 'Ocupación']" :alineadas="[2, 3, 4, 5]">
    @forelse ($tablero->demandaHoraria as $franja)
        <tr wire:key="franja-{{ $franja->horaInicio }}-{{ $franja->horaFin }}" class="border-b border-separador">
            <td class="py-2.5 pr-3 font-medium whitespace-nowrap">{{ $franja->horaInicio }} – {{ $franja->horaFin }}</td>
            <td class="py-2.5 pr-3 text-terciario">{{ DiasDeClase::texto($franja->dias) }}</td>
            <td class="py-2.5 pr-3 text-right text-apagado">{{ $franja->grupos }}</td>
            <td class="py-2.5 pr-3 text-right font-semibold">{{ $franja->matriculados }}</td>
            <td class="py-2.5 pr-3 text-right text-apagado">{{ $franja->cupo }}</td>
            <td class="py-2.5 text-right">{{ $porcentaje($franja->ocupacion()) }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="py-3 text-apagado">No hay horarios registrados en este periodo.</td></tr>
    @endforelse
</x-ciln.tabla>

<x-ciln.tabla titulo="Rentabilidad por grupo" :columnas="['Grupo', 'Idioma', 'Nivel', 'Matrículas', 'Ingresos', 'Horas dictadas', 'Costo docente', 'Margen']" :alineadas="[3, 4, 5, 6, 7]">
    @forelse ($tablero->rentabilidad->filas as $fila)
        <tr wire:key="rentabilidad-tabla-{{ $fila->grupoId }}" class="border-b border-separador">
            <td class="py-2.5 pr-3 font-medium">{{ $fila->codigo }}</td>
            <td class="py-2.5 pr-3">{{ $fila->idioma }}</td>
            <td class="py-2.5 pr-3 text-terciario">{{ $fila->nivel }}</td>
            <td class="py-2.5 pr-3 text-right text-apagado">{{ $fila->matriculas }}</td>
            <td class="py-2.5 pr-3 text-right whitespace-nowrap">{{ $soles($fila->ingresosCentimos) }}</td>
            <td class="py-2.5 pr-3 text-right whitespace-nowrap text-apagado">{{ $horas($fila->horasCentesimas) }}@if ($fila->horasSinCostoCentesimas > 0) <span class="text-ambar-texto">+ {{ $horas($fila->horasSinCostoCentesimas) }} sin costo</span>@endif</td>
            <td class="py-2.5 pr-3 text-right whitespace-nowrap">{{ $soles($fila->costoCentimos) }}</td>
            <td @class(['py-2.5 text-right font-semibold whitespace-nowrap', 'text-verde-texto' => $fila->margen() >= 0, 'text-rojo-texto' => $fila->margen() < 0])>{{ $soles($fila->margen()) }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="py-3 text-apagado">No hay grupos en este periodo.</td></tr>
    @endforelse
    <tr class="font-semibold">
        <td class="py-2.5 pr-3" colspan="4">Total</td>
        <td class="py-2.5 pr-3 text-right whitespace-nowrap">{{ $soles($tablero->rentabilidad->ingresosCentimos) }}</td>
        <td></td>
        <td class="py-2.5 pr-3 text-right whitespace-nowrap">{{ $soles($tablero->rentabilidad->costoCentimos) }}</td>
        <td class="py-2.5 text-right whitespace-nowrap">{{ $soles($tablero->rentabilidad->margen()) }}</td>
    </tr>
</x-ciln.tabla>
