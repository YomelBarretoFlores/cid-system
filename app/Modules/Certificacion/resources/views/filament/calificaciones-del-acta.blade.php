@use('App\Modules\Certificacion\Data\ActaDelGrupo')
@use('App\Modules\Certificacion\Enums\EstadoFinal')
@use('App\Shared\Ui\Formato')
@php
    $acta = ActaDelGrupo::desde($getRecord()->loadMissing('notas'));
    $colores = [EstadoFinal::Aprobado->value => 'success', EstadoFinal::Desaprobado->value => 'danger', EstadoFinal::Nsp->value => 'gray'];
@endphp
<div class="overflow-x-auto">
    <table class="tabla-reporte">
        <thead>
            <tr>
                <th scope="col">ID estudiante</th>
                <th scope="col">Estudiante</th>
                @foreach ($acta->componentes as $componente)
                    <th scope="col" class="text-right">{{ $componente->nombre }} ({{ Formato::nota($componente->peso) }} %)</th>
                @endforeach
                <th scope="col" class="text-right">Promedio final</th>
                <th scope="col">Estado final</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($acta->filas as $fila)
                <tr wire:key="nota-final-{{ $fila->matriculaId }}">
                    <td>{{ $fila->documento }}</td>
                    <td>{{ $fila->estudiante }}</td>
                    @foreach ($acta->componentes as $componente)
                        <td class="text-right">{{ Formato::nota($fila->notas[$componente->id] ?? null) }}</td>
                    @endforeach
                    <td class="text-right font-semibold">{{ $fila->promedio ?? '—' }}</td>
                    <td><x-filament::badge :color="$colores[$fila->estadoFinal->value]">{{ $fila->estadoFinal->etiqueta() }}</x-filament::badge></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
