<?php

declare(strict_types=1);

use App\Modules\Matricula\Http\Controllers\Api\ListarMisMatriculas;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'estudiante', 'estudiante.habilitado'])->prefix('estudiante')->group(function (): void {
    Route::get('/matriculas', ListarMisMatriculas::class)->name('api.estudiante.matriculas');
});
