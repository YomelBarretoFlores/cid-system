@props(['tinte' => 'bg-hoja-celeste', 'idiomas' => [], 'logo' => false])
@php
    $hojas = [[8, 10, -24, 54], [72, 6, 18, 48], [4, 64, -36, 46], [78, 62, 12, 52], [40, 80, -8, 40]];
@endphp
<div aria-hidden="true" {{ $attributes->class(['relative overflow-hidden rounded-[28px]', $tinte]) }}>
    <div class="absolute inset-[14%] rounded-full bg-white/60 blur-2xl"></div>
    @if ($logo)
        <img src="{{ asset('images/ciln/logo.webp') }}" alt="" width="720" height="768" class="absolute top-1/2 left-1/2 h-auto w-[58%] -translate-x-1/2 -translate-y-1/2 select-none">
    @else
        <x-ciln.emblema decorativo class="absolute top-1/2 left-1/2 w-[64%] -translate-x-1/2 -translate-y-1/2" />
    @endif
    @foreach ($idiomas === [] ? [] : $hojas as [$x, $y, $giro, $tamano])
        <span class="flotar absolute drop-shadow-[0_4px_8px_rgb(10_46_82/0.14)]" style="left: {{ $x }}%; top: {{ $y }}%; --orden: {{ $loop->index }}">
            <x-ciln.hoja-bandera :idioma="$idiomas[$loop->index % count($idiomas)]" :size="$tamano" :rot="$giro" forma="hoja" />
        </span>
    @endforeach
</div>
