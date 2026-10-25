@php($notificaciones = $getRecord()->notificaciones()->get())
@if ($notificaciones->isEmpty())
    <p class="text-sm text-gray-500">Aún no se enviaron correos.</p>
@else
    <div class="overflow-x-auto">
        <table class="tabla-reporte">
            <thead>
                <tr><th scope="col">Fecha y hora</th><th scope="col">Destinatario</th><th scope="col">Asunto</th><th scope="col">Estado de entrega</th><th scope="col" class="text-right">Intentos</th></tr>
            </thead>
            <tbody>
                @foreach ($notificaciones as $notificacion)
                    <tr>
                        <td class="whitespace-nowrap">{{ ($notificacion->enviado_en ?? $notificacion->created_at)->timezone('America/Lima')->format('d/m/Y H:i') }}</td>
                        <td>{{ $notificacion->destinatario }}</td>
                        <td>{{ $notificacion->asunto }}</td>
                        <td>{{ $notificacion->estado->etiqueta() }}@if ($notificacion->error) · {{ $notificacion->error }}@endif</td>
                        <td class="text-right">{{ $notificacion->intentos }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
