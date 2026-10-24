@props(['titulo', 'descripcion' => null, 'ancho' => 'max-w-md'])
<section {{ $attributes->class(['mx-auto w-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8', $ancho]) }}>
    <header class="mb-6 flex flex-col gap-1">
        <h1 class="text-xl font-semibold text-ciln-900">{{ $titulo }}</h1>
        @if ($descripcion)
            <p class="text-sm text-slate-600">{{ $descripcion }}</p>
        @endif
    </header>

    {{ $slot }}
</section>
