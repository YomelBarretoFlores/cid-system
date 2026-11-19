<?php

declare(strict_types=1);

use App\Modules\Personas\Http\Controllers\VerFotoParaPersonal;
use App\Modules\Personas\Http\Controllers\VerFotoPropia;
use App\Modules\Personas\Livewire\InicioDelPortal;
use App\Modules\Personas\Livewire\ModificarDatos;
use App\Modules\Personas\Livewire\Preferencias;
use App\Modules\Personas\Livewire\Registro;
use App\Modules\Personas\Livewire\SubirFotografia;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/privacidad', 'personas::privacidad')->name('privacidad');

Route::livewire('/estudiante/registro', Registro::class)->middleware('guest')->name('estudiante.registro');

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante', InicioDelPortal::class)->name('estudiante.inicio');
    Route::livewire('/estudiante/perfil/datos', ModificarDatos::class)->name('estudiante.perfil.datos');
    Route::livewire('/estudiante/perfil/foto', SubirFotografia::class)->name('estudiante.perfil.foto');
    Route::get('/estudiante/perfil/foto/imagen', VerFotoPropia::class)->name('estudiante.perfil.foto.ver');
    Route::livewire('/estudiante/preferencias', Preferencias::class)->name('estudiante.preferencias');
});

Route::get('/panel/personas/{persona}/foto', VerFotoParaPersonal::class)
    ->middleware(['auth', 'auth.session', 'panel.sesion', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:view,persona'])
    ->name('panel.personas.foto');
