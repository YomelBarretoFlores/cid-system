<?php

declare(strict_types=1);

use App\Shared\Auth\Http\Controllers\CerrarSesion;
use App\Shared\Auth\Http\Controllers\ConfirmarCorreo;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Livewire\RestablecerClave;
use App\Shared\Auth\Livewire\SolicitarRestablecimiento;
use App\Shared\Auth\Livewire\VerificarCorreo;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/estudiante/ingresar', IniciarSesion::class)->name('estudiante.ingresar');
    Route::livewire('/recuperar-clave', SolicitarRestablecimiento::class)->name('password.request');
    Route::livewire('/restablecer-clave/{token}', RestablecerClave::class)->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::livewire('/verificar-correo', VerificarCorreo::class)->name('verification.notice');
    Route::get('/verificar-correo/{id}/{hash}', ConfirmarCorreo::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/estudiante/salir', CerrarSesion::class)->name('estudiante.salir');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('/estudiante', 'portal.inicio')->name('estudiante.inicio');
});
