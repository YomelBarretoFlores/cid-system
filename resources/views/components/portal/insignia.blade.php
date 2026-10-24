@props(['color' => 'gray'])
<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
    'bg-amber-50 text-amber-900 ring-amber-300' => $color === 'warning',
    'bg-green-50 text-green-900 ring-green-300' => $color === 'success',
    'bg-red-50 text-red-900 ring-red-300' => $color === 'danger',
    'bg-ciln-50 text-ciln-900 ring-ciln-100' => $color === 'info',
    'bg-slate-100 text-slate-800 ring-slate-300' => $color === 'gray',
]) }}>{{ $slot }}</span>
