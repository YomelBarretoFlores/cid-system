@php($tramite = $getRecord()->load('movimientos.actor'))
<div class="overflow-x-auto">
    <table class="tabla-reporte">
        <thead>
            <tr><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col">Etapa</th><th scope="col">Comentario</th><th scope="col">Por</th></tr>
        </thead>
        <tbody>
            @foreach ($tramite->movimientos as $movimiento)
                <tr>
                    <td class="whitespace-nowrap">{{ $movimiento->created_at->timezone('America/Lima')->format('d/m/Y H:i') }}</td>
                    <td>{{ $movimiento->estado->etiqueta() }}</td>
                    <td>{{ $movimiento->etapa_nombre }}</td>
                    <td>{{ $movimiento->comentario ?? '—' }}</td>
                    <td>{{ $movimiento->actor?->name ?? 'Sistema' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
