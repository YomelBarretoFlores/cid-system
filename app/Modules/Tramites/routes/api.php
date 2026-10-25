<?php

declare(strict_types=1);

use App\Modules\Tramites\Http\Controllers\Api\ListarMisTramites;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'correo.verificado', 'estudiante'])->prefix('estudiante')->group(function (): void {
    Route::get('/tramites', ListarMisTramites::class)->name('api.estudiante.tramites');
});
