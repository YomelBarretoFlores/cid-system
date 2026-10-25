@props(['href', 'emblema' => 'w-[52px]'])
<a href="{{ $href }}" {{ $attributes->class('flex items-center gap-3 text-tinta') }}>
    <x-ciln.emblema :class="$emblema" />
    <span class="flex flex-col leading-[1.1]">
        <span class="text-[17px] font-semibold">Centro de Idiomas</span>
        <span class="text-xs tracking-[0.12em] text-apagado">UNASAM</span>
    </span>
</a>
