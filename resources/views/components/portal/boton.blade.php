@props(['accion' => null])
<button {{ $attributes->merge(['type' => 'submit'])->class('inline-flex items-center justify-center gap-2 rounded-md bg-ciln-700 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-ciln-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ciln-700 disabled:cursor-wait disabled:opacity-70') }}
    @if ($accion) wire:loading.attr="disabled" wire:target="{{ $accion }}" @endif>
    {{ $slot }}
</button>
