@use('App\Modules\Certificacion\Data\ActaDelGrupo')
@php
    $acta = ActaDelGrupo::desde($getRecord()->loadMissing('notas'));
@endphp
<div class="overflow-x-auto">
    <table class="tabla-reporte">
        <thead>
            <tr>
                <th scope="col">ID estudiante</th>
                <th scope="col">Estudiante</th>
                @foreach ($acta->columnas() as $componente)
                    <th scope="col" class="text-right">{{ $componente->nombre }}</th>
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
                    @foreach ($acta->columnas() as $componente)
                        <td class="text-right">{{ $fila->celda($componente) }}</td>
                    @endforeach
                    <td class="text-right font-semibold">{{ $fila->promedio ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ $fila->estadoFinal->etiqueta() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
