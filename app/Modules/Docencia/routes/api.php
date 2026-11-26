<?php

declare(strict_types=1);

use App\Modules\Docencia\Http\Controllers\Api\ListarMisGrupos;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'docente'])->prefix('docente')->group(function (): void {
    Route::get('/grupos', ListarMisGrupos::class)->name('api.docente.grupos');
});
