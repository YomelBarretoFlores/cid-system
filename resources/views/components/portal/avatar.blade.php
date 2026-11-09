@props(['nombre', 'foto' => null])
@if ($foto)
    <img src="{{ $foto }}" alt="" {{ $attributes->class('shrink-0 rounded-full bg-acento-claro object-cover ring-acento-claro') }}>
@else
    @php($iniciales = collect(preg_split('/[\s,]+/', trim((string) $nombre)) ?: [])->filter()->take(2)->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))->implode(''))
    <span aria-hidden="true" {{ $attributes->class('flex shrink-0 items-center justify-center rounded-full bg-acento-claro font-semibold text-acento ring-acento-claro') }}>{{ $iniciales }}</span>
@endif
