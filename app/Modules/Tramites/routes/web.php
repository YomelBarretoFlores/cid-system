<?php

declare(strict_types=1);

use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Livewire\PortadaPublica;
use Illuminate\Support\Facades\Route;

Route::livewire('/', PortadaPublica::class)->name('portada');

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/tramites', MisTramites::class)->name('estudiante.tramites');
});
