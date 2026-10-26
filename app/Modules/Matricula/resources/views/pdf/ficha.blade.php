@use('App\Modules\Matricula\Enums\EstadoMatricula')
@use('App\Shared\Ui\CodigoDeBarras')
{{-- Laid out after the UNASAM SGA enrollment record; DOMPDF draws tables and simple CSS only (ADR 0005). --}}
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Ficha de Matrícula {{ $ficha->periodo }}</title>
        <style>
            @page { margin: 1.2cm 1.3cm 1.6cm; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #1d1b16; line-height: 1.35; }
            .encabezado { width: 100%; border-bottom: 1px solid #eae9e5; padding-bottom: 8px; }
            .encabezado td { vertical-align: middle; }
            .institucion { text-align: center; font-size: 11pt; }
            .oficina { font-size: 8pt; color: #5e5a50; }
            .emision { text-align: right; font-size: 6.5pt; color: #5e5a50; margin-top: 6px; }
            h1 { font-size: 17pt; font-weight: normal; margin: 4px 0 6px; }
            h1 span { color: #8d887c; font-size: 12pt; }
            .apagado { color: #8d887c; }
            .estudiante { width: 100%; margin-bottom: 12px; }
            .estudiante td { padding: 1px 0; }
            table.matriculas { width: 100%; border-collapse: collapse; }
            table.matriculas th { text-align: left; font-size: 7.5pt; padding: 4px 5px; border: 1px solid #dddad3; }
            table.matriculas td { padding: 5px; border: 1px solid #dddad3; vertical-align: middle; }
            .detalle { font-size: 6.5pt; color: #6e695e; }
            .centro { text-align: center; }
            .monto { text-align: right; white-space: nowrap; }
            .total td { font-weight: bold; }
            .retirada { color: #8e2a1c; font-size: 6.5pt; }
            .validacion { font-size: 6.5pt; color: #6e695e; margin-top: 14px; }
        </style>
    </head>
    <body>
        <table class="encabezado">
            <tr>
                <td style="width: 32%;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/unasam.png'))) }}" alt="" width="170"></td>
                <td class="institucion">
                    Universidad Nacional Santiago Antúnez de Mayolo<br>
                    <span class="oficina">Centro de Idiomas y Lenguas Nativas</span>
                </td>
                <td style="width: 32%; text-align: right;"><img src="data:image/png;base64,{{ base64_encode((string) file_get_contents(resource_path('images/pdf/emblema.png'))) }}" alt="" width="46"></td>
            </tr>
        </table>

        <div class="emision">{{ $ficha->emitidaEn->format('Y-m-d H:i:s') }}<br>{{ $ficha->codigo }}</div>

        <h1>Ficha de Matrícula <span>{{ $ficha->periodo }}</span></h1>

        <table class="estudiante">
            <tr>
                <td>{{ mb_strtoupper($ficha->estudiante->nombreCompleto) }} <span class="apagado">{{ $ficha->estudiante->documento }}</span></td>
                <td style="text-align: right;"><strong>Fecha de matrícula:</strong> {{ $ficha->primeraMatricula()?->format('d/m/Y h:i A') ?? '—' }}</td>
            </tr>
        </table>

        <div style="font-size: 10pt; margin-bottom: 4px;">Matrículas</div>
        <table class="matriculas">
            <thead>
                <tr>
                    <th style="width: 4%;">N°</th>
                    <th>Idioma y ciclo</th>
                    <th style="width: 19%;">Horario</th>
                    <th style="width: 10%;">Aula</th>
                    <th style="width: 9%;" class="centro">Condición</th>
                    <th style="width: 12%;">Pago</th>
                    <th style="width: 9%;" class="monto">Costo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ficha->matriculas as $matricula)
                    @php($grupo = $matricula->grupo)
                    <tr>
                        <td class="centro">{{ $loop->iteration }}</td>
                        <td>
                            {{ mb_strtoupper($grupo->idioma) }} · Ciclo {{ $grupo->cicloNumero }} ({{ $grupo->nivel }})
                            @if ($matricula->estado === EstadoMatricula::Retirada)
                                <span class="retirada">· {{ $matricula->estado->etiqueta() }}</span>
                            @endif
                            <div class="detalle">
                                Grupo {{ $grupo->codigo }} · {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }} · Docente: {{ $matricula->docente ?? 'Por asignar' }} · Matriculado el {{ $matricula->matriculadoEn->format('d/m/Y h:i A') }}
                            </div>
                        </td>
                        <td>{{ $grupo->horarios === [] ? 'Por confirmar' : implode(' · ', $grupo->horarios) }}</td>
                        <td>
                            {{ $grupo->aula ?? ($grupo->enlaceVirtual !== null ? 'Virtual' : 'Por asignar') }}
                            @if ($grupo->aula === null && $grupo->enlaceVirtual !== null)
                                <div class="detalle">{{ $grupo->enlaceVirtual }}</div>
                            @endif
                        </td>
                        <td class="centro">{{ $matricula->condicion->etiqueta() }}</td>
                        <td>
                            {{ $matricula->estadoDelPago() }}
                            @if ($matricula->pago !== null)
                                <div class="detalle">{{ $matricula->pago->concepto }} · {{ $matricula->pago->numeroOperacion }} ({{ $matricula->pago->medioPago }}) · {{ $matricula->pago->fechaPago->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="monto">{{ $matricula->pago !== null ? 'S/ '.$matricula->pago->monto : '—' }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="5">
                        <img src="{{ CodigoDeBarras::comoDataUri($ficha->codigo, 1, 34) }}" alt="" height="26">
                    </td>
                    <td>Total pagado:</td>
                    <td class="monto">S/ {{ $ficha->totalPagado() }}</td>
                </tr>
            </tbody>
        </table>

        <div class="validacion">
            Ficha emitida por el Sistema de Gestión Académica del CILN el {{ $ficha->emitidaEn->format('d/m/Y h:i A') }}, con <strong>{{ count($ficha->matriculas) }}</strong> matrícula(s) registradas en el periodo {{ $ficha->periodo }}. Un pago verificado fue validado por Caja del CILN.
        </div>
    </body>
</html>
