<x-pdf.constancia-por-nivel :documento="$documento" titulo="CONSTANCIA DE NOTAS" :institucion="$institucion ?? app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)()"
    :firmas="$firmas ?? []" :qr="$qr" :verificacion="$verificacion" :emitido-el="$emitidoEl" :denominacion="$denominacion ?? null" />
