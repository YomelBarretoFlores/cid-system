@props(['title' => null])
@use('App\Modules\Docencia\Contracts\ClasesPorConfirmarDelDelegado')
@use('App\Modules\Docencia\Contracts\DocenteDelUsuario')
@use('App\Modules\Personas\Contracts\FichaDelUsuario')
@use('App\Modules\Tramites\Contracts\TramitesObservados')
@use('App\Shared\Auth\Models\User')
@php
    $usuario = auth()->user();
    $verificado = $usuario?->hasVerifiedEmail() ?? false;
    $esDocente = $verificado && app(DocenteDelUsuario::class)((int) $usuario->id) !== null;
    $personaId = $verificado ? app(FichaDelUsuario::class)((int) $usuario->id)?->personaId : null;
    $observados = $personaId === null ? 0 : app(TramitesObservados::class)($personaId);
    $porConfirmar = $personaId === null ? null : app(ClasesPorConfirmarDelDelegado::class)($personaId);
    $colapsado = request()->cookie('ciln-menu-colapsado') === '1';
@endphp
<x-layouts.base :title="$title"
    x-data="{ colapsado: {{ $colapsado ? 'true' : 'false' }}, animar: false, alternar() { this.animar = true; this.colapsado = ! this.colapsado; document.cookie = 'ciln-menu-colapsado=' + (this.colapsado ? '1' : '0') + '; path=/; max-age=31536000; samesite=lax'; } }"
    :data-colapsado="$colapsado ? 'true' : false" x-bind:data-colapsado="colapsado ? 'true' : null" x-bind:class="{ 'transition-[grid-template-columns]': animar }"
    class="grid grid-cols-[minmax(0,1fr)] content-start gap-2 sm:gap-3.5 lg:grid-cols-[264px_minmax(0,1fr)] lg:content-stretch lg:data-colapsado:grid-cols-[88px_minmax(0,1fr)]">
    <aside x-data="{ abierto: false }" class="flex flex-col gap-5 rounded-tarjeta bg-tarjeta px-4 py-5 lg:sticky lg:top-[30px] lg:max-h-[calc(100vh-60px)] lg:overflow-auto lg:px-[18px] lg:pt-[22px] lg:in-data-colapsado:px-3">
        <div class="flex items-center justify-between gap-3 lg:in-data-colapsado:flex-col">
            <a href="{{ route('estudiante.inicio') }}" wire:navigate.hover class="flex items-center gap-3 px-1.5 text-tinta lg:in-data-colapsado:px-0">
                <x-ciln.emblema class="w-11 lg:in-data-colapsado:w-10" />
                <span class="flex flex-col leading-[1.1] lg:in-data-colapsado:hidden">
                    <span class="text-[17px]/[1.1] font-semibold tracking-[-0.01em] whitespace-nowrap">Centro de Idiomas</span>
                    <span class="text-[12px] tracking-[0.12em] text-apagado">UNASAM</span>
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
                <div class="flex flex-col items-center gap-2 border-b border-borde-interno pb-5 text-center" title="{{ $usuario->name }}">
                    <x-portal.avatar :nombre="$usuario->name" class="size-[72px] text-xl ring-4 lg:in-data-colapsado:size-11 lg:in-data-colapsado:text-sm lg:in-data-colapsado:ring-2" />
                    <span class="flex min-w-0 flex-col lg:in-data-colapsado:sr-only">
                        <span class="text-[15px] leading-snug font-semibold text-balance">{{ $usuario->name }}</span>
                        <span class="text-[13px] text-apagado">Estudiante</span>
                    </span>
                </div>

                @if ($verificado)
                    <nav aria-label="Portal del estudiante" class="flex shrink-0 flex-col gap-4">
                        <x-portal.grupo-menu titulo="Mi cuenta">
                            <x-portal.enlace-menu ruta="estudiante.inicio" icono="heroicon-o-user-circle">Perfil</x-portal.enlace-menu>
                        </x-portal.grupo-menu>
                        <x-portal.grupo-menu titulo="Reportes">
                            <x-portal.enlace-menu ruta="estudiante.reportes" icono="heroicon-o-squares-2x2">Todos</x-portal.enlace-menu>
                        </x-portal.grupo-menu>
                        <x-portal.grupo-menu titulo="Procesos">
                            <x-portal.enlace-menu ruta="estudiante.matricula" icono="heroicon-o-pencil-square">Matrícula</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.matriculas" activo="estudiante.matriculas*" icono="heroicon-o-academic-cap">Mis matrículas</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.pagos" icono="heroicon-o-banknotes">Mis pagos</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.clasificacion" icono="heroicon-o-clipboard-document-check">Clasificación</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.tramites" icono="heroicon-o-document-text" :insignia="$observados ?: null">Trámites</x-portal.enlace-menu>
                            @if ($porConfirmar !== null)
                                <x-portal.enlace-menu ruta="estudiante.clases" icono="heroicon-o-check-badge" :insignia="$porConfirmar ?: null">Confirmar clases</x-portal.enlace-menu>
                            @endif
                        </x-portal.grupo-menu>
                        <x-portal.grupo-menu titulo="Enlaces">
                            <x-portal.enlace-menu ruta="verificacion.documento" icono="heroicon-o-qr-code" :navegar="false">Verificar un documento</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="portada" icono="heroicon-o-globe-alt" :navegar="false">Web del CILN</x-portal.enlace-menu>
                        </x-portal.grupo-menu>
                    </nav>
                @endif

                <div class="mt-auto flex flex-col gap-4 border-t border-borde-interno pt-4">
                    <button type="button" x-on:click="alternar()" x-bind:aria-expanded="! colapsado" aria-controls="menu-del-portal"
                        x-bind:title="colapsado ? 'Expandir el menú' : null"
                        class="hidden items-center gap-3 self-start rounded-xl px-1.5 py-1 text-[14.5px] text-apagado hover:text-tinta focus-visible:shadow-foco focus-visible:outline-none lg:flex lg:in-data-colapsado:self-center lg:in-data-colapsado:px-0">
                        <x-heroicon-o-chevron-double-left aria-hidden="true" class="size-[19px] transition-transform lg:in-data-colapsado:rotate-180" />
                        <span x-text="colapsado ? 'Expandir el menú' : 'Contraer el menú'" class="lg:in-data-colapsado:sr-only">{{ $colapsado ? 'Expandir el menú' : 'Contraer el menú' }}</span>
                    </button>
                    <div class="flex flex-col gap-2.5 px-1.5 text-[14.5px] lg:in-data-colapsado:items-center lg:in-data-colapsado:px-0">
                        @if ($esDocente)
                            <a href="{{ route('filament.docente.pages.mis-grupos') }}" title="Portal docente" class="flex items-center gap-3 text-tinta hover:text-enlace-hover">
                                <x-heroicon-o-user-group aria-hidden="true" class="size-[19px] text-apagado" /><span class="lg:in-data-colapsado:sr-only">Portal docente</span>
                            </a>
                        @endif
                        @can(User::ADMIN_PANEL_PERMISSION)
                            <a href="{{ route('filament.admin.pages.dashboard') }}" title="Panel administrativo" class="flex items-center gap-3 text-tinta hover:text-enlace-hover">
                                <x-heroicon-o-squares-2x2 aria-hidden="true" class="size-[19px] text-apagado" /><span class="lg:in-data-colapsado:sr-only">Panel administrativo</span>
                            </a>
                        @endcan
                        <form method="POST" action="{{ route('estudiante.salir') }}">
                            @csrf
                            <button type="submit" title="Cerrar sesión" class="flex items-center gap-3 text-peligro hover:underline focus-visible:shadow-foco focus-visible:outline-none">
                                <x-heroicon-o-arrow-right-start-on-rectangle aria-hidden="true" class="size-[19px]" /><span class="lg:in-data-colapsado:sr-only">Cerrar sesión</span>
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
