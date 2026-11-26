<?php

declare(strict_types=1);

use App\Modules\Personas\Http\Controllers\Api\MostrarPerfil;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'estudiante'])->prefix('estudiante')->group(function (): void {
    Route::get('/perfil', MostrarPerfil::class)->name('api.estudiante.perfil');
});
