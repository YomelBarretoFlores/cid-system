<?php

declare(strict_types=1);

use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Http\Controllers\DescargarAdjuntoDelTramite;
use App\Modules\Tramites\Http\Controllers\VerCargoDelTramite;
use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Livewire\PortadaPublica;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Route;

Route::livewire('/', PortadaPublica::class)->name('portada');

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/tramites', MisTramites::class)->name('estudiante.tramites');
    Route::get('/estudiante/tramites/{tramite:codigo}/cargo', VerCargoDelTramite::class)->where('tramite', '[A-Za-z0-9-]{1,20}')->name('estudiante.tramites.cargo');
});

Route::get('/panel/tramites/adjuntos/{adjunto:ulid}', DescargarAdjuntoDelTramite::class)
    ->middleware(['auth', 'auth.session', 'panel.sesion', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:'.PermisoTramite::Gestionar->value])
    ->name('panel.tramites.adjuntos');
