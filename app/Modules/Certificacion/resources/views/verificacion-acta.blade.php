@use('App\Modules\Certificacion\Enums\EstadoActa')
@use('App\Shared\Ui\Formato')
<x-layouts.base title="Verificar acta" :indexable="false" class="flex items-start justify-center sm:items-center">
    <main id="contenido" class="flex w-full max-w-[640px] flex-col gap-3.5 py-4 sm:py-10">
        <header class="flex items-center gap-3 rounded-tarjeta bg-tarjeta p-5 sm:px-7">
            <x-ciln.emblema class="w-[52px]" />
            <span class="flex flex-col leading-[1.15]">
                <span class="text-[17px] font-semibold">Centro de Idiomas y Lenguas Nativas</span>
                <span class="text-[13px] text-apagado">Universidad Nacional Santiago Antúnez de Mayolo · Verificación de actas</span>
            </span>
        </header>

        <section class="flex flex-col gap-5 rounded-tarjeta bg-tarjeta p-5 sm:px-7 sm:py-[26px]" aria-labelledby="resultado">
            <div>
                @if ($acta->estado === EstadoActa::Cerrada)
                    <x-portal.insignia color="success">Acta final auténtica y cerrada</x-portal.insignia>
                @else
                    <x-portal.insignia color="warning">Acta en rectificación</x-portal.insignia>
                @endif
            </div>
            <h1 id="resultado" class="text-titulo font-semibold tracking-[-0.02em]">Acta final de evaluación</h1>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <x-portal.dato etiqueta="Grupo">{{ $acta->grupo_codigo }}</x-portal.dato>
                <x-portal.dato etiqueta="Idioma">{{ $acta->idioma }}</x-portal.dato>
                <x-portal.dato etiqueta="Nivel y ciclo">{{ $acta->nivel->etiqueta() }} · {{ $acta->ciclo_nombre }}</x-portal.dato>
                <x-portal.dato etiqueta="Periodo académico">{{ $acta->periodo }}</x-portal.dato>
                <x-portal.dato etiqueta="Estado">{{ $acta->estado->etiqueta() }}</x-portal.dato>
                <x-portal.dato etiqueta="Validada y cerrada el">{{ $acta->validada_en ? Formato::fechaCorta($acta->validada_en->setTimezone('America/Lima')) : '—' }}</x-portal.dato>
                <x-portal.dato etiqueta="Estudiantes en el acta">{{ $acta->notas_count }} {{ $acta->notas_count === 1 ? 'estudiante' : 'estudiantes' }}</x-portal.dato>
            </dl>
            @unless ($acta->estado === EstadoActa::Cerrada)
                <x-portal.alerta tipo="info">El acta se está rectificando: la versión impresa puede no ser la vigente hasta que se vuelva a cerrar.</x-portal.alerta>
            @endunless
            <p class="text-[13px] text-apagado">Por protección de datos personales, esta página no muestra a los estudiantes ni sus notas.</p>
        </section>
    </main>
</x-layouts.base>
