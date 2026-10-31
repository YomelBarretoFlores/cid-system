@use('App\Shared\Ui\PaletaCiln')
@php($fondo = fn (int $color): string => 'background: '.($pdf ? PaletaCiln::color($color) : PaletaCiln::variable($color)).';')
{{-- One body for the PDF, the print view and the reports page, laid out like the SGA "Horario personalizado". --}}
<h2 class="titulo-formal">Cursos y docentes</h2>
<div class="overflow-x-auto">
    <table class="tabla-formal min-w-[720px]">
        <tbody>
            @foreach ($horario->grupos as $grupo)
                <tr>
                    <td rowspan="2" class="centro" style="{{ $fondo($grupo->color) }} width: 32px; font-weight: bold;">[{{ $grupo->numero }}]</td>
                    <td colspan="8" style="font-weight: bold;">{{ mb_strtoupper("{$grupo->idioma} · Ciclo {$grupo->cicloNumero} ({$grupo->nivel})") }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Grupo</td>
                    <td>{{ $grupo->codigo }}</td>
                    <td style="font-weight: bold;">Docente</td>
                    <td>{{ $grupo->docente }}</td>
                    <td style="font-weight: bold;">Aula</td>
                    <td>
                        {{ $grupo->lugar() }}
                        @if ($grupo->enlaceVirtual !== null)
                            <br><a href="{{ $grupo->enlaceVirtual }}" target="_blank" rel="noopener noreferrer" class="enlace break-all">{{ $grupo->enlaceVirtual }}</a>
                        @endif
                    </td>
                    <td style="font-weight: bold;">Modalidad</td>
                    <td>{{ $grupo->modalidad }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2 class="titulo-formal">Mi horario</h2>
<div class="overflow-x-auto">
    <table class="tabla-formal min-w-[720px]">
        <thead>
            <tr>
                <th style="width: 13%;">HORA</th>
                @foreach ($horario->dias as $dia)
                    <th>{{ mb_strtoupper($dia) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($horario->franjas as $franja)
                @if ($franja->sinClases)
                    <tr><td colspan="{{ count($horario->dias) + 1 }}" class="separador">SIN CLASES</td></tr>
                @else
                    <tr>
                        <td class="centro" style="white-space: nowrap;">{{ $franja->horas }}</td>
                        @foreach (array_keys($horario->dias) as $dia)
                            @php($celdas = $franja->celdas[$dia] ?? [])
                            <td class="centro" @if ($celdas !== []) style="{{ $fondo($celdas[0]->color) }}" @endif>
                                @foreach ($celdas as $celda)
                                    {{ $celda->etiqueta() }}@if (! $loop->last)<br>@endif
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
