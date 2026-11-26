@php
    $grupo = $documento->grupo;
    $institucion = app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)();
@endphp
<x-matricula::pdf.plantilla titulo="CONSTANCIA DE INSCRIPCIÓN" :documento="$documento">
    <p style="text-align: justify; margin-top: 24px;">
        El {{ $institucion->centro }} de la {{ $institucion->universidad }} hace constar que
        <strong>{{ $documento->estudiante->nombreCompleto }}</strong>, identificado(a) con {{ $documento->estudiante->documento }},
        se encuentra inscrito(a) en el grupo <strong>{{ $grupo->codigo }}</strong> de {{ $grupo->idioma }}, Ciclo {{ $grupo->cicloNumero }}
        ({{ $grupo->nivel }}), modalidad {{ mb_strtolower($grupo->modalidad) }}, del periodo {{ $grupo->periodo }},
        con matrícula registrada el {{ $documento->matriculadoEn->format('d/m/Y') }}.
    </p>

    <p style="text-align: justify;">{{ app(\App\Modules\Certificacion\Contracts\TextosDeDocumentos::class)()->cierreConstanciaMatricula }}</p>

    <p style="text-align: right; margin-top: 36px;">{{ $institucion->ciudad }}, {{ $emitidaEl }}</p>
</x-matricula::pdf.plantilla>
