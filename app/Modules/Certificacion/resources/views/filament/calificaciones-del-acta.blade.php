@use('App\Modules\Academico\Enums\GrupoDeComponente')
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
                <th scope="col" rowspan="2">ID estudiante</th>
                <th scope="col" rowspan="2">Estudiante</th>
                @foreach (GrupoDeComponente::cases() as $grupo)
                    <th scope="colgroup" class="text-center" colspan="{{ count($acta->componentesDe($grupo)) + 1 }}">{{ $grupo->etiqueta() }}</th>
                @endforeach
                <th scope="col" class="text-right" rowspan="2">Promedio final</th>
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
                <tr wire:key="nota-final-{{ $fila->matriculaId }}">
                    <td>{{ $fila->documento }}</td>
                    <td>{{ $fila->estudiante }}</td>
                    @foreach (GrupoDeComponente::cases() as $grupo)
                        @foreach ($acta->componentesDe($grupo) as $componente)
                            <td class="text-right">{{ Formato::nota($fila->notas[$componente->id] ?? null) }}</td>
                        @endforeach
                        <td class="text-right">{{ $fila->promedioDe($grupo) ?? '—' }}</td>
                    @endforeach
                    <td class="text-right font-semibold">{{ $fila->promedio ?? '—' }}</td>
                    <td><x-filament::badge :color="$colores[$fila->estadoFinal->value]">{{ $fila->estadoFinal->etiqueta() }}</x-filament::badge></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
