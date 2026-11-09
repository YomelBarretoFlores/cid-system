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
    $ficha = $verificado ? app(FichaDelUsuario::class)((int) $usuario->id) : null;
    $personaId = $ficha?->personaId;
    $foto = $ficha?->versionDeFoto === null ? null : route('estudiante.perfil.foto.ver', ['v' => $ficha->versionDeFoto]);
    $observados = $personaId === null ? 0 : app(TramitesObservados::class)($personaId);
    $porConfirmar = $personaId === null ? null : app(ClasesPorConfirmarDelDelegado::class)($personaId);
    $colapsado = request()->cookie('ciln-menu-colapsado') === '1';
    $atajo = str_contains((string) request()->userAgent(), 'Mac') ? '⌘ B' : 'Ctrl B';
@endphp
<x-layouts.base :title="$title" tema="tema-portal"
    x-data="{ colapsado: {{ $colapsado ? 'true' : 'false' }}, animar: false, alternar() { this.animar = true; this.colapsado = ! this.colapsado; document.cookie = 'ciln-menu-colapsado=' + (this.colapsado ? '1' : '0') + '; path=/; max-age=31536000; samesite=lax'; } }"
    x-on:keydown.window="if ((($event.ctrlKey || $event.metaKey) && ! $event.shiftKey && ! $event.altKey && $event.key.toLowerCase() === 'b') && ! $event.target.isContentEditable) { $event.preventDefault(); alternar() }"
    :data-colapsado="$colapsado ? 'true' : false" x-bind:data-colapsado="colapsado ? 'true' : null" x-bind:class="{ 'transition-[grid-template-columns]': animar }"
    class="grid grid-cols-[minmax(0,1fr)] content-start gap-2 sm:gap-3.5 lg:grid-cols-[264px_minmax(0,1fr)] lg:content-stretch lg:data-colapsado:grid-cols-[76px_minmax(0,1fr)]">
    <aside x-data="{ abierto: false }" class="flex flex-col rounded-tarjeta border border-borde-interno bg-tarjeta lg:sticky lg:top-[30px] lg:h-[calc(100vh-60px)]">
        <div class="flex items-center justify-between gap-3 px-4 py-4 lg:in-data-colapsado:justify-center lg:in-data-colapsado:px-2">
            <a href="{{ route('estudiante.inicio') }}" wire:navigate.hover class="flex items-center gap-3 text-tinta">
                <x-ciln.emblema class="w-10 lg:in-data-colapsado:w-9" />
                <span class="flex flex-col leading-[1.15] lg:in-data-colapsado:hidden">
                    <span class="text-[16px] font-semibold whitespace-nowrap">Centro de Idiomas</span>
                    <span class="text-[12px] tracking-[0.08em] text-apagado">UNASAM</span>
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
            <div id="menu-del-portal" class="flex min-h-0 flex-1 flex-col max-lg:hidden" x-bind:class="{ 'max-lg:hidden': ! abierto }">
                <div class="flex flex-col items-center gap-2 border-y border-borde-interno px-4 py-4 text-center lg:in-data-colapsado:px-2" title="{{ $usuario->name }}">
                    <x-portal.avatar :nombre="$usuario->name" :foto="$foto" class="size-16 text-lg lg:in-data-colapsado:size-10 lg:in-data-colapsado:text-sm" />
                    <span class="flex min-w-0 flex-col lg:in-data-colapsado:sr-only">
                        <span class="text-[14.5px] leading-snug font-semibold text-balance">{{ $usuario->name }}</span>
                        <span class="text-[13px] text-apagado">Estudiante</span>
                    </span>
                </div>

                <div class="h-4 shrink-0"></div>
                @if ($verificado)
                    <nav aria-label="Portal del estudiante" class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-3 pb-4 lg:in-data-colapsado:px-2">
                        <x-portal.grupo-menu titulo="Mi cuenta">
                            <x-portal.enlace-menu ruta="estudiante.inicio" icono="heroicon-o-user-circle">Perfil</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.perfil.datos" icono="heroicon-o-pencil">Modificar datos</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.perfil.foto" icono="heroicon-o-camera">Subir fotografía</x-portal.enlace-menu>
                        </x-portal.grupo-menu>
                        <x-portal.grupo-menu titulo="Reportes">
                            @php($vista = request()->routeIs('estudiante.reportes') ? request()->query('ver') : null)
                            <x-portal.enlace-menu ruta="estudiante.reportes" icono="heroicon-o-squares-2x2" :actual="request()->routeIs('estudiante.reportes') && ! in_array($vista, ['horario', 'silabos'], true)">Todos</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.reportes" :parametros="['ver' => 'horario']" icono="heroicon-o-calendar-days" :actual="$vista === 'horario'">Horario</x-portal.enlace-menu>
                            <x-portal.enlace-menu ruta="estudiante.reportes" :parametros="['ver' => 'silabos']" icono="heroicon-o-book-open" :actual="$vista === 'silabos'">Sílabos</x-portal.enlace-menu>
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

                <div class="flex flex-col gap-1 border-t border-borde-interno px-3 py-3 lg:in-data-colapsado:px-2">
                    <div x-data="{ cuenta: false }" x-on:click.outside="cuenta = false" x-on:keydown.escape="cuenta = false" class="relative">
                        <button type="button" x-on:click="cuenta = ! cuenta" x-bind:aria-expanded="cuenta" aria-haspopup="menu" aria-controls="menu-de-cuenta"
                            class="flex w-full items-center gap-3 rounded-control px-2 py-2 text-left hover:bg-hover-suave focus-visible:shadow-foco focus-visible:outline-none lg:in-data-colapsado:justify-center lg:in-data-colapsado:px-0">
                            <x-portal.avatar :nombre="$usuario->name" :foto="$foto" class="size-8 text-[12px]" />
                            <span class="min-w-0 flex-1 truncate text-[14px] font-medium lg:in-data-colapsado:sr-only">{{ $usuario->name }}</span>
                            <x-heroicon-o-chevron-up-down aria-hidden="true" class="size-4 shrink-0 text-apagado lg:in-data-colapsado:hidden" />
                        </button>
                        <div id="menu-de-cuenta" role="menu" x-show="cuenta" x-cloak x-transition.opacity.duration.100ms
                            class="absolute bottom-full left-0 z-40 mb-2 flex w-full min-w-[230px] flex-col gap-0.5 rounded-interna bg-tarjeta p-1.5 text-[14px] shadow-dropdown lg:in-data-colapsado:bottom-0 lg:in-data-colapsado:left-full lg:in-data-colapsado:mb-0 lg:in-data-colapsado:ml-2">
                            <a href="{{ route('estudiante.inicio') }}" wire:navigate role="menuitem" class="flex items-center gap-2.5 rounded-control px-2.5 py-2 hover:bg-hover-suave"><x-heroicon-o-user-circle aria-hidden="true" class="size-[18px] text-apagado" />Mi perfil</a>
                            @if ($esDocente)
                                <a href="{{ route('filament.docente.pages.mis-grupos') }}" role="menuitem" class="flex items-center gap-2.5 rounded-control px-2.5 py-2 hover:bg-hover-suave"><x-heroicon-o-user-group aria-hidden="true" class="size-[18px] text-apagado" />Portal docente</a>
                            @endif
                            @can(User::ADMIN_PANEL_PERMISSION)
                                <a href="{{ route('filament.admin.pages.dashboard') }}" role="menuitem" class="flex items-center gap-2.5 rounded-control px-2.5 py-2 hover:bg-hover-suave"><x-heroicon-o-squares-2x2 aria-hidden="true" class="size-[18px] text-apagado" />Panel administrativo</a>
                            @endcan
                            <div class="my-1 border-t border-separador"></div>
                            <form method="POST" action="{{ route('estudiante.salir') }}">
                                @csrf
                                <button type="submit" role="menuitem" class="flex w-full items-center gap-2.5 rounded-control px-2.5 py-2 text-peligro hover:bg-hover-suave"><x-heroicon-o-arrow-right-start-on-rectangle aria-hidden="true" class="size-[18px]" />Cerrar sesión</button>
                            </form>
                        </div>
                    </div>
                    <button type="button" x-on:click="alternar()" x-bind:aria-expanded="! colapsado" aria-controls="menu-del-portal"
                        x-bind:title="colapsado ? 'Expandir el menú (Ctrl o ⌘ + B)' : 'Contraer el menú (Ctrl o ⌘ + B)'"
                        class="hidden items-center gap-3 rounded-control px-2.5 py-1.5 text-[14px] text-apagado hover:text-tinta focus-visible:shadow-foco focus-visible:outline-none lg:flex lg:in-data-colapsado:justify-center lg:in-data-colapsado:px-0">
                        <x-heroicon-o-chevron-double-left aria-hidden="true" class="size-[18px] shrink-0 transition-transform lg:in-data-colapsado:rotate-180" />
                        <span class="flex flex-1 items-center justify-between gap-2 lg:in-data-colapsado:sr-only">
                            <span x-text="colapsado ? 'Expandir el menú' : 'Contraer el menú'">{{ $colapsado ? 'Expandir el menú' : 'Contraer el menú' }}</span>
                            <kbd class="font-sans text-[12px] text-apagado">{{ $atajo }}</kbd>
                        </span>
                    </button>
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
