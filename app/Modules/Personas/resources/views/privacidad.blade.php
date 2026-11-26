@php
    $institucion = app(\App\Modules\Certificacion\Contracts\DatosInstitucionales::class)();
    $politica = app(\App\Modules\Personas\Actions\ArmarPoliticaDePrivacidad::class)();
    $actualizada = app(\App\Modules\Personas\Actions\ConsultarTextosLegales::class)()->fechaDeActualizacion();
@endphp
<x-layouts.base title="Política de privacidad" :marco="false">
<div class="tema-publico flex min-h-screen flex-col bg-papel text-marino-900">
    <x-publico.cabecera />
    <main id="contenido" class="mx-auto flex w-full max-w-[760px] flex-1 flex-col px-4 pt-10 pb-24 sm:px-6 sm:pt-16">
        <article class="flex flex-col gap-5 border border-piedra-300 bg-white p-5 text-[15px] leading-relaxed text-pretty sm:p-8">
            <header class="flex flex-col gap-1 border-b border-piedra-300 pb-4">
                <p class="text-[13.5px] text-marino-800/80">{{ $institucion->universidad }} · {{ $institucion->centro }}</p>
                <h1 class="font-display text-[clamp(24px,2.4vw,30px)] leading-tight font-semibold">Política de privacidad y tratamiento de datos personales</h1>
                <p class="text-[13.5px] text-marino-800/80">Última actualización: {{ \App\Shared\Ui\Formato::fechaLarga($actualizada) }}</p>
            </header>

            {{-- Escaped Markdown (ArmarPoliticaDePrivacidad): headings open each section like the original layout. --}}
            <div class="flex flex-col gap-3 [&_h2]:mt-2 [&_h2]:border-t [&_h2]:border-piedra-300 [&_h2]:pt-5 [&_h2]:text-[17px] [&_h2]:font-semibold [&_h2]:text-marino-900 [&_h3]:font-semibold [&_ul]:flex [&_ul]:list-none [&_ul]:flex-col [&_ul]:gap-1.5 [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_table]:block [&_table]:overflow-x-auto [&_a]:enlace">
                {{ $politica }}
            </div>
        </article>
    </main>
    <x-publico.pie :verificar="false" />
</div>
</x-layouts.base>
