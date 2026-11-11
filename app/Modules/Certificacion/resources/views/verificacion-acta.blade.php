@use('App\Modules\Certificacion\Enums\EstadoActa')
@use('App\Shared\Ui\Formato')
<x-layouts.base title="Verificar acta" :indexable="false" :marco="false">
<div class="tema-publico flex min-h-screen flex-col bg-papel text-marino-900">
    <x-publico.cabecera />
    <main id="contenido" class="mx-auto flex w-full max-w-[720px] flex-1 flex-col gap-4 px-4 pt-12 pb-24 sm:px-6 sm:pt-16">
        <x-publico.titulo resaltado="acta" etiqueta="Verificación de actas" nivel="p">Verifica un</x-publico.titulo>

        <section class="flex flex-col gap-5 rounded-tarjeta border border-marino-900/12 bg-white p-5 sm:px-7 sm:py-[26px]" aria-labelledby="resultado">
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
    <x-publico.pie :verificar="false" />
</div>
</x-layouts.base>
