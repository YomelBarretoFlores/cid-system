<ol data-pasos-del-llamado class="grid gap-2 sm:grid-flow-col sm:auto-cols-fr" aria-label="Llamados de la clase">
    @foreach ($pasos as [$numero, $etiqueta, $estado])
        <li @class(['flex items-center gap-3 rounded-interna px-3 py-2.5',
            'bg-acento-claro' => $estado === 'En curso', 'bg-lienzo' => $estado !== 'En curso'])>
            <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                'bg-tinta text-white' => $estado === 'En curso',
                'bg-positivo text-white' => in_array($estado, ['Tomado', 'Cerrado', 'Registrado'], true),
                'border-2 border-piedra-300 text-apagado' => $estado === 'Pendiente'])>{{ $numero }}</span>
            <span class="flex flex-col leading-tight">
                <span @class(['text-sm text-tinta', 'font-semibold' => $estado === 'En curso'])>{{ $etiqueta }}</span>
                <span class="text-xs text-terciario">{{ $estado }}</span>
            </span>
        </li>
    @endforeach
</ol>
