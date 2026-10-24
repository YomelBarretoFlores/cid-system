@php($grupo = $documento->grupo)
<x-matricula::pdf.plantilla titulo="CONSTANCIA DE INSCRIPCIÓN" :documento="$documento">
    <p style="text-align: justify; margin-top: 24px;">
        El Centro de Idiomas y Lenguas Nativas de la Universidad Nacional Santiago Antúnez de Mayolo hace constar que
        <strong>{{ $documento->estudiante->nombreCompleto }}</strong>, identificado(a) con {{ $documento->estudiante->documento }},
        se encuentra inscrito(a) en el grupo <strong>{{ $grupo->codigo }}</strong> de {{ $grupo->idioma }}, Ciclo {{ $grupo->cicloNumero }}
        ({{ $grupo->nivel }}), modalidad {{ mb_strtolower($grupo->modalidad) }}, del periodo {{ $grupo->periodo }},
        con matrícula registrada el {{ $documento->matriculadoEn->format('d/m/Y') }}.
    </p>

    <p style="text-align: justify;">Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.</p>

    <p style="text-align: right; margin-top: 36px;">Huaraz, {{ $emitidaEl }}</p>
</x-matricula::pdf.plantilla>
