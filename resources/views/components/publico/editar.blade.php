@props(['atajo' => null, 'claro' => false])
{{-- Shown only to staff who may edit the home content; the panel opens at the matching form section. --}}
@if ($atajo)
    <a href="{{ $atajo['url'] }}" data-editar-seccion {{ $attributes->class([
        'inline-flex w-fit rounded-[3px] border px-2 py-0.5 text-[12.5px] font-semibold underline-offset-3 transition-colors hover:underline focus-visible:ring-3 focus-visible:outline-none print:hidden',
        'border-white/50 text-white focus-visible:ring-celeste-400/60' => $claro,
        'border-marino-900/30 bg-white text-real-600 hover:text-marino-900 focus-visible:ring-real-600/40' => ! $claro,
    ]) }}>Editar<span class="sr-only"> sección {{ $atajo['nombre'] }}</span></a>
@endif
