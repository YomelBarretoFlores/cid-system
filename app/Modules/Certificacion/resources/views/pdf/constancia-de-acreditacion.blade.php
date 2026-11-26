<x-pdf.constancia-por-nivel :documento="$documento" titulo="CONSTANCIA DE ACREDITACIÓN" :institucion="$institucion ?? app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)()"
    :firmas="$firmas ?? []" :qr="$qr" :verificacion="$verificacion" :emitido-el="$emitidoEl" :denominacion="$denominacion ?? null" />
