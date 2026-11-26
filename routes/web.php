<?php

declare(strict_types=1);

use App\Shared\Auth\Http\Controllers\CerrarSesion;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Livewire\RestablecerClave;
use App\Shared\Auth\Livewire\SolicitarRestablecimiento;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/ingresar', IniciarSesion::class)->name('ingresar');
    Route::livewire('/recuperar-clave', SolicitarRestablecimiento::class)->name('password.request');
    Route::livewire('/restablecer-clave/{token}', RestablecerClave::class)->name('password.reset');
});

// The former separate entries for students and teachers keep working for saved links.
Route::redirect('/estudiante/ingresar', '/ingresar');
Route::redirect('/docente/ingresar', '/ingresar');

Route::middleware('auth')->group(function (): void {
    Route::post('/estudiante/salir', CerrarSesion::class)->name('estudiante.salir');
});
