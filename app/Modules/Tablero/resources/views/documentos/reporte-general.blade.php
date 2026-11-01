{{-- Body shared by the report page and its PDF: one formal table per section. --}}
@foreach ($reporte->secciones as $seccion)
    <h2 class="titulo-formal">{{ $seccion->titulo }}</h2>
    <table class="tabla-formal">
        <thead>
            <tr>
                @foreach ($seccion->columnas as $columna)
                    <th @class(['centro' => $columna->tipo->esNumero()])>{{ $columna->nombre }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($seccion->filas as $fila)
                <tr>
                    @foreach ($seccion->columnas as $indice => $columna)
                        <td @class(['centro' => $columna->tipo->esNumero()])>{{ $columna->tipo->mostrar($fila[$indice] ?? null) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($seccion->columnas) }}">Sin registros para los filtros elegidos.</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
