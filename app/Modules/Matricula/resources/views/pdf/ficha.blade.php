@use('App\Modules\Matricula\Enums\EstadoMatricula')
@use('App\Shared\Ui\CodigoDeBarras')
@use('App\Shared\Ui\Formato')
{{-- Same sheet, margins, font and sizes as the UNASAM SGA enrollment record (A3 page, DejaVu Sans); DOMPDF draws tables and simple CSS only (ADR 0005). The public copy leaves out the document number, the payment operation and the virtual class link. --}}
@php($documento = $publica ? Formato::documentoOculto($ficha->estudiante->documento) : $ficha->estudiante->documento)
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Ficha de Matrícula {{ $ficha->periodo }}</title>
        <style>
            @page { margin: 28.5pt 34pt 34pt 34.5pt; }
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.4pt; color: #212529; line-height: 1.2; }
            .emision { text-align: right; font-size: 5.4pt; line-height: 1.35; margin-top: 19pt; }
            h1 { font-size: 18pt; font-weight: normal; margin: -11pt 0 0; }
            h1 span { font-size: 12pt; color: #868e96; }
            .estudiante { font-size: 10.8pt; margin-top: 6pt; }
            .estudiante span { font-size: 8.4pt; color: #868e96; }
            table.datos { width: 100%; margin-top: 1pt; }
            table.datos td { padding: 0; }
            .derecha { text-align: right; }
            .subtitulo { font-size: 10.8pt; margin: 6pt 0 3pt; }
            table.cursos { width: 100%; border-collapse: collapse; font-size: 7.2pt; }
            table.cursos th { text-align: left; font-weight: bold; padding: 4pt; border: 0.6pt solid #dee2e6; border-bottom: 1.2pt solid #dee2e6; }
            table.cursos td { padding: 3.5pt 4pt; border: 0.6pt solid #dee2e6; vertical-align: middle; }
            table.cursos table td { border: 0; padding: 0; }
            .centro { text-align: center; }
            .segunda { font-size: 6pt; color: #495057; }
            .hora { font-size: 6pt; color: #868e96; text-align: right; }
            .retirada { color: #c92a2a; }
            .total td { font-weight: bold; }
            .pie { font-size: 6pt; color: #868e96; }
        </style>
    </head>
    <body>
        <x-matricula::pdf.membrete laterales="150pt" />

        <div class="emision">{{ $ficha->emitidaEn->format('Y-m-d H:i:s') }}<br>{{ $ficha->codigo }}</div>

        <h1>Ficha de Matrícula <span>{{ $ficha->periodo }}</span></h1>

        <div class="estudiante">{{ mb_strtoupper($ficha->estudiante->nombreCompleto) }} <span>{{ $documento }}</span></div>
        <table class="datos">
            <tr>
                <td style="width: 52%;"><strong>Centro:</strong> CENTRO DE IDIOMAS Y LENGUAS NATIVAS</td>
                <td style="width: 18%;"><strong>Matrículas:</strong> {{ count($ficha->matriculas) }}</td>
                <td class="derecha"><strong>Fecha de matrícula:</strong> {{ $ficha->primeraMatricula()?->format('d/m/Y h:i A') ?? '—' }}</td>
            </tr>
            <tr><td colspan="3" class="derecha">{{ $documento }}</td></tr>
        </table>

        <div class="subtitulo">Cursos</div>
        <table class="cursos">
            <thead>
                <tr>
                    <th style="width: 40pt;">N°</th>
                    <th>Curso</th>
                    <th style="width: 54pt;" class="centro">Grupo</th>
                    <th style="width: 42pt;" class="centro">Ciclo</th>
                    <th style="width: 50pt;" class="centro">Nivel</th>
                    <th style="width: 44pt;" class="centro">Condición</th>
                    <th style="width: 38pt;" class="derecha">Costo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ficha->matriculas as $matricula)
                    @php($grupo = $matricula->grupo)
                    <tr>
                        <td class="centro">{{ $loop->iteration }}</td>
                        <td>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td>
                                        {{ mb_strtoupper($grupo->idioma) }} {{ mb_strtoupper($grupo->nivel) }}
                                        @if ($matricula->estado === EstadoMatricula::Retirada)
                                            <span class="retirada">· {{ $matricula->estado->etiqueta() }}</span>
                                        @endif
                                        <div class="segunda">
                                            {{ $grupo->modalidad }}{{ $grupo->esAcelerado ? ' · Acelerado' : '' }}
                                            · {{ $grupo->aula ?? ($grupo->enlaceVirtual !== null ? 'Virtual' : 'Aula por asignar') }}
                                            · Docente: {{ $matricula->docente ?? 'Por asignar' }}
                                            · Pago: {{ $matricula->estadoDelPago() }}@if (! $publica && $matricula->pago !== null) ({{ $matricula->pago->concepto }} · {{ $matricula->pago->numeroOperacion }} · {{ $matricula->pago->medioPago }} · {{ $matricula->pago->fechaPago->format('d/m/Y') }})@endif
                                            @if (! $publica && $grupo->aula === null && $grupo->enlaceVirtual !== null)
                                                · {{ $grupo->enlaceVirtual }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="hora" style="padding-left: 6pt; width: 72pt; vertical-align: bottom;">{{ $matricula->matriculadoEn->format('d/m/Y h:i A') }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="centro">{{ $grupo->codigo }}</td>
                        <td class="centro ciclo">{{ Formato::romano($grupo->cicloNumero) }}</td>
                        <td class="centro">{{ $grupo->nivel }}</td>
                        <td class="centro">{{ $matricula->condicion->etiqueta() }}</td>
                        <td class="derecha costo">{{ $matricula->centimosPagados() > 0 ? $matricula->pago?->monto : '—' }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3" class="derecha" style="font-weight: normal; font-size: 4.8pt; vertical-align: bottom;">{{ mb_strtoupper($ficha->estudiante->nombreCompleto) }} - {{ $documento }} - {{ $ficha->codigo }}</td>
                    <td>Matrículas:</td>
                    <td class="centro">{{ count($ficha->matriculas) }}</td>
                    <td>Costo:</td>
                    <td class="derecha">S/{{ $ficha->totalPagado() }}</td>
                </tr>
                <tr class="total">
                    <td colspan="4" rowspan="2"><img src="{{ CodigoDeBarras::comoDataUri($ficha->codigo, 1, 30) }}" alt="" height="22"></td>
                    <td colspan="2">Pagos verificados:</td>
                    <td class="derecha pagos-verificados">{{ $ficha->pagosVerificados() }}</td>
                </tr>
                <tr class="total">
                    <td colspan="2">Costo total:</td>
                    <td class="derecha total-pagado">S/{{ $ficha->totalPagado() }}</td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 30pt;">
            <tr>
                <td class="pie" style="vertical-align: top;">
                    <strong>Puede verificar la validez de esta constancia en la siguiente url:</strong><br>
                    {{ $verificacion }}<br><br>
                    @if ($ficha->validadaEn() !== null)
                        Su matrícula fue validada por <strong>CAJA DEL CILN</strong> en fecha y hora <strong>{{ $ficha->validadaEn()->format('d/m/Y h:i A') }}</strong>, con <strong>{{ count($ficha->matriculas) }}</strong> curso(s) registrados.
                    @else
                        Su matrícula aún no tiene pagos validados por Caja del CILN, con <strong>{{ count($ficha->matriculas) }}</strong> curso(s) registrados.
                    @endif
                </td>
                <td style="width: 80pt; text-align: right; vertical-align: top;"><img src="{{ $qr }}" alt="" width="76" height="76"></td>
            </tr>
        </table>
    </body>
</html>
