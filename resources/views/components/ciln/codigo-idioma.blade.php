@props(['idioma', 'tamano' => 'normal'])
{{-- A language as its three-letter code: flags stand for countries, and native languages such as Quechua have none. --}}
@php($codigo = mb_strtoupper(mb_substr(\Illuminate\Support\Str::ascii((string) $idioma), 0, 3)))
<span title="{{ $idioma }}" {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-control bg-acento-claro font-semibold tracking-[0.06em] text-oro-800',
    'h-7 min-w-11 px-2 text-[12px]' => $tamano === 'normal',
    'h-10 min-w-14 px-2.5 text-[14px]' => $tamano === 'grande',
]) }}>{{ $codigo }}<span class="sr-only"> ({{ $idioma }})</span></span>
