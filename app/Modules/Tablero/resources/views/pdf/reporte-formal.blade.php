{{-- A general report on the institutional register sheet of the previous system: one heading and one black-ruled table per section, totals in bold. --}}
<x-pdf.registro :titulo="$reporte->titulo" :subtitulo="$reporte->periodo" :horizontal="false">
    <table class="datos">
        <tr>
            <td>{{ collect($reporte->alcance)->map(fn (string $valor, string $filtro): string => "{$filtro}: {$valor}")->implode(' · ') }}</td>
            <td style="text-align: right;">Emitido: {{ $emitido }}</td>
        </tr>
    </table>

    @forelse ($reporte->secciones as $seccion)
        <h2>{{ mb_strtoupper($seccion->encabezado ?? $seccion->titulo) }}</h2>
        <table class="registro">
            <thead>
                @if (collect($seccion->columnas)->contains(fn ($columna): bool => $columna->grupo !== null))
                    @php($grupos = collect($seccion->columnas)->chunkWhile(fn ($columna, $indice, $anteriores): bool => $columna->grupo !== null && $columna->grupo === $anteriores->last()->grupo))
                    <tr>
                        @foreach ($grupos as $bloque)
                            @if ($bloque->first()->grupo === null)
                                <th rowspan="2">{{ mb_strtoupper($bloque->first()->nombre) }}</th>
                            @else
                                <th colspan="{{ $bloque->count() }}">{{ mb_strtoupper($bloque->first()->grupo) }}</th>
                            @endif
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($seccion->columnas as $columna)
                            @if ($columna->grupo !== null)
                                <th>{{ mb_strtoupper($columna->corto ?? $columna->nombre) }}</th>
                            @endif
                        @endforeach
                    </tr>
                @else
                    <tr>
                        @foreach ($seccion->columnas as $columna)
                            <th>{{ mb_strtoupper($columna->nombre) }}</th>
                        @endforeach
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse ($seccion->filas as $fila)
                    <tr>
                        @foreach ($seccion->columnas as $indice => $columna)
                            <td @style(['text-align: center' => $columna->tipo->esNumero()])>{{ $columna->tipo->mostrar($fila[$indice] ?? null) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($seccion->columnas) }}">Sin registros para los filtros elegidos.</td></tr>
                @endforelse
                @foreach ($seccion->totales as $fila)
                    <tr class="total">
                        @foreach ($seccion->columnas as $indice => $columna)
                            <td @style(['text-align: center' => $columna->tipo->esNumero()])>{{ $fila[$indice] === null ? '' : mb_strtoupper($columna->tipo->mostrar($fila[$indice])) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="nota">Sin registros para los filtros elegidos.</p>
    @endforelse
</x-pdf.registro>
