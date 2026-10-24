<?php

declare(strict_types=1);

use App\Modules\Personas\Livewire\InicioDelPortal;
use App\Modules\Personas\Livewire\Registro;
use Illuminate\Support\Facades\Route;

Route::livewire('/estudiante/registro', Registro::class)->middleware('guest')->name('estudiante.registro');

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante', InicioDelPortal::class)->name('estudiante.inicio');
});
