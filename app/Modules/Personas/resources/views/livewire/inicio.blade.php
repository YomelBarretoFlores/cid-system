<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold text-ciln-900">Hola, {{ $ficha->nombres }}</h1>
        <p class="text-sm text-slate-600">{{ $ficha->nombreCompleto }} · {{ $ficha->documento }}</p>
    </header>
</div>
