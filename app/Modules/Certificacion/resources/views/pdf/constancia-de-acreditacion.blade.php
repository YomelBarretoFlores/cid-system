{{-- Records issued before the real accreditation format (detalle version 2) keep the per-level grade record layout. --}}
@if (($documento->detalle['version'] ?? 2) >= 3)
    @include('certificacion::pdf.acreditacion-de-idioma-extranjero')
@else
    <x-pdf.constancia-por-nivel :documento="$documento" titulo="CONSTANCIA DE ACREDITACIÓN" :institucion="$institucion ?? app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)()"
        :firmas="$firmas ?? []" :qr="$qr" :verificacion="$verificacion" :emitido-el="$emitidoEl" :denominacion="$denominacion ?? null" />
@endif
