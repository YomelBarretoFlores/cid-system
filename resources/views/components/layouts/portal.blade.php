@props(['title' => null])
@use('App\Modules\Docencia\Contracts\DocenteDelUsuario')
@use('App\Shared\Auth\Models\User')
@php
    $usuario = auth()->user();
    $verificado = $usuario?->hasVerifiedEmail() ?? false;
    $esDocente = $verificado && app(DocenteDelUsuario::class)((int) $usuario->id) !== null;
    $iniciales = collect(preg_split('/\s+/', trim((string) $usuario?->name)) ?: [])->filter()->take(2)->map(fn (string $parte): string => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
@endphp
<x-layouts.base :title="$title" class="grid grid-cols-[minmax(0,1fr)] content-start gap-2 sm:gap-3.5 lg:grid-cols-[264px_minmax(0,1fr)] lg:content-stretch">
    <aside x-data="{ abierto: false }" class="flex flex-col gap-5 rounded-tarjeta bg-tarjeta px-4 py-5 lg:sticky lg:top-[30px] lg:max-h-[calc(100vh-60px)] lg:overflow-auto lg:px-[18px] lg:pt-[26px]">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('estudiante.inicio') }}" class="flex items-center gap-3 px-1.5 text-tinta">
                <x-ciln.emblema class="w-[54px]" />
                <span class="flex flex-col leading-[1.1]">
                    <span class="text-lg/[1.1] font-semibold tracking-[-0.01em] whitespace-nowrap">Centro de Idiomas</span>
                    <span class="text-[12.5px] tracking-[0.12em] text-apagado">{{ request()->routeIs('docente.*') ? 'PORTAL DOCENTE' : 'UNASAM' }}</span>
                </span>
            </a>
            @auth
                <button type="button" x-on:click="abierto = ! abierto" x-bind:aria-expanded="abierto" aria-controls="menu-del-portal"
                    class="rounded-control p-2 text-secundario hover:bg-hover-suave focus-visible:shadow-foco focus-visible:outline-none lg:hidden">
                    <span class="sr-only">Menú</span>
                    <x-heroicon-o-bars-3 class="size-6" x-show="! abierto" />
                    <x-heroicon-o-x-mark class="size-6" x-show="abierto" x-cloak />
                </button>
            @endauth
        </div>

        @auth
            <div id="menu-del-portal" class="flex flex-1 flex-col gap-5 max-lg:hidden" x-bind:class="{ 'max-lg:hidden': ! abierto }">
                @if ($verificado)
                    <nav aria-label="Portal del estudiante" class="flex shrink-0 flex-col gap-0.5">
                        <x-portal.enlace-menu ruta="estudiante.inicio" icono="heroicon-o-home">Inicio</x-portal.enlace-menu>
                        <x-portal.enlace-menu ruta="estudiante.pagos" icono="heroicon-o-banknotes">Mis pagos</x-portal.enlace-menu>
                        <x-portal.enlace-menu ruta="estudiante.matricula" icono="heroicon-o-pencil-square">Matrícula</x-portal.enlace-menu>
                        <x-portal.enlace-menu ruta="estudiante.matriculas" icono="heroicon-o-academic-cap">Mis matrículas</x-portal.enlace-menu>
                        <x-portal.enlace-menu ruta="estudiante.clasificacion" icono="heroicon-o-clipboard-document-check">Clasificación</x-portal.enlace-menu>
                        @if ($esDocente)
                            <x-portal.enlace-menu ruta="docente.grupos" activo="docente.*" icono="heroicon-o-user-group">Mis grupos</x-portal.enlace-menu>
                        @endif
                    </nav>
                @endif

                <div class="mt-auto flex flex-col gap-4">
                    <div class="flex items-center gap-3 px-1.5">
                        <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-oro-100 font-semibold text-oro-950">{{ $iniciales }}</span>
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span class="truncate font-medium">{{ $usuario->name }}</span>
                            <span class="self-start rounded-pildora bg-oro-300 px-2 text-xs text-oro-950">{{ $esDocente ? 'Docente' : 'Estudiante' }}</span>
                        </span>
                    </div>
                    <div class="flex flex-col gap-2.5 px-1.5 text-[14.5px]">
                        @can(User::ADMIN_PANEL_PERMISSION)
                            <a href="{{ route('filament.admin.pages.dashboard') }}" class="flex items-center gap-3 text-tinta hover:text-enlace-hover">
                                <x-heroicon-o-squares-2x2 class="size-[19px] text-apagado" />Panel administrativo
                            </a>
                        @endcan
                        <form method="POST" action="{{ route('estudiante.salir') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 text-peligro hover:underline focus-visible:shadow-foco focus-visible:outline-none">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="size-[19px]" />Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endauth
    </aside>

    <main id="contenido" class="flex min-w-0 flex-col gap-2 sm:gap-3.5">
        @if (session('estado'))
            <x-portal.alerta tipo="exito">{{ session('estado') }}</x-portal.alerta>
        @endif
        @if (session('error'))
            <x-portal.alerta tipo="error">{{ session('error') }}</x-portal.alerta>
        @endif

        {{ $slot }}
    </main>
</x-layouts.base>
