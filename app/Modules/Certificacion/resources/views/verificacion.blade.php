<x-layouts.base title="Verificar documento" :indexable="false" :marco="false">
<div class="tema-publico flex min-h-screen flex-col bg-papel text-marino-900">
    <x-publico.cabecera />
    <main id="contenido" class="mx-auto flex w-full max-w-[720px] flex-1 flex-col gap-4 px-4 pt-12 pb-24 sm:px-6 sm:pt-16">
        <x-publico.titulo resaltado="documento" etiqueta="Verificación de documentos" :nivel="$verificacion ? 'p' : 'h1'">Verifica un</x-publico.titulo>

        @if ($verificacion)
            <section class="relative flex flex-col gap-5 overflow-hidden rounded-tarjeta border border-marino-900/12 bg-white p-5 sm:px-7 sm:py-[26px]" aria-labelledby="resultado">
                <x-ciln.emblema decorativo class="pointer-events-none absolute -top-[50px] -right-[50px] w-[220px] opacity-[0.07]" />
                <div class="flex flex-wrap items-center gap-2.5">
                    @if ($verificacion->vigente)
                        <x-portal.insignia color="success">Documento auténtico y vigente</x-portal.insignia>
                    @else
                        <x-portal.insignia color="danger">Documento anulado el {{ $verificacion->anuladoEl }}</x-portal.insignia>
                    @endif
                </div>
                <h1 id="resultado" class="text-titulo font-semibold tracking-[-0.02em]">{{ $verificacion->tipo }}</h1>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <x-portal.dato etiqueta="Nombres">{{ $verificacion->estudiante }}</x-portal.dato>
                    <x-portal.dato etiqueta="Documento de identidad">{{ $verificacion->documentoOculto }}</x-portal.dato>
                    <x-portal.dato etiqueta="Idioma">{{ $verificacion->idioma }}</x-portal.dato>
                    @if ($verificacion->nivel)
                        <x-portal.dato :etiqueta="$verificacion->esCertificado ? 'Nivel alcanzado' : 'Nivel'">{{ $verificacion->nivel }}</x-portal.dato>
                    @endif
                    @if ($verificacion->horasLectivas)
                        <x-portal.dato etiqueta="Total de horas lectivas">{{ $verificacion->horasLectivas }}</x-portal.dato>
                    @endif
                    @if ($verificacion->notaFinal)
                        <x-portal.dato etiqueta="Nota final">{{ $verificacion->notaFinal }}</x-portal.dato>
                    @endif
                    <x-portal.dato etiqueta="Detalle" class="sm:col-span-2">{{ $verificacion->resumen }}</x-portal.dato>
                    <x-portal.dato etiqueta="Código único">{{ $verificacion->codigo }}</x-portal.dato>
                    <x-portal.dato etiqueta="Fecha de emisión">{{ $verificacion->emitidoEl }}</x-portal.dato>
                </dl>
            </section>
        @elseif ($codigo)
            <x-portal.alerta tipo="error">No encontramos un documento con el código {{ $codigo }}. Revisa que esté escrito tal como aparece en el documento.</x-portal.alerta>
        @endif

        <form method="GET" action="{{ route('verificacion.documento') }}" class="flex flex-col gap-3 rounded-tarjeta border border-marino-900/12 bg-white p-5 sm:px-7">
            <label for="codigo" class="font-medium">{{ $verificacion || $codigo ? 'Verificar otro documento' : 'Escribe el código único del documento' }}</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input id="codigo" name="codigo" type="text" required maxlength="30" autocomplete="off" placeholder="CILN-XXXXXXXXXX" class="control flex-1 uppercase">
                <x-publico.boton>Verificar</x-publico.boton>
            </div>
            <p class="text-[13px] text-apagado">El código está junto al QR, al pie de cada constancia o certificado del CILN.</p>
        </form>
    </main>
    <x-publico.pie :verificar="false" />
</div>
</x-layouts.base>
