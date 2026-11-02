<ol data-pasos-del-llamado class="grid gap-2 sm:grid-flow-col sm:auto-cols-fr" aria-label="Llamados de la clase">
    @foreach ($pasos as [$numero, $etiqueta, $estado, $tipo])
        <li @class(['flex items-center gap-3 rounded-interna px-3 py-2.5', 'bg-acento-claro' => $tipo === 'actual', 'bg-lienzo' => $tipo !== 'actual'])>
            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                'bg-tinta text-white' => $tipo === 'actual', 'bg-positivo text-white' => $tipo === 'hecho', 'border-2 border-piedra-300 text-apagado' => $tipo === 'pendiente'])>{{ $numero }}</span>
            <span class="flex flex-col leading-tight">
                <span @class(['text-sm text-tinta', 'font-semibold' => $tipo === 'actual'])>{{ $etiqueta }}</span>
                <span class="text-xs text-terciario">{{ $estado }}</span>
            </span>
        </li>
    @endforeach
</ol>
