<?php

declare(strict_types=1);

use App\Modules\Certificacion\Http\Controllers\Api\DescargarMiDocumento;
use App\Modules\Certificacion\Http\Controllers\Api\ListarMisDocumentos;
use App\Modules\Certificacion\Http\Controllers\Api\ListarMisNotas;
use App\Modules\Certificacion\Http\Controllers\Api\VerificarDocumentoPorApi;
use Illuminate\Support\Facades\Route;

Route::get('/documentos/{codigo}', VerificarDocumentoPorApi::class)
    ->where('codigo', '[A-Za-z0-9-]{1,30}')
    ->middleware('throttle:30,1')
    ->name('api.documentos.verificar');

Route::middleware(['auth:sanctum', 'correo.verificado', 'estudiante'])->prefix('estudiante')->group(function (): void {
    Route::get('/notas', ListarMisNotas::class)->name('api.estudiante.notas');
    Route::get('/documentos', ListarMisDocumentos::class)->name('api.estudiante.documentos');
    // Each download renders a PDF, so it gets a tighter limit than the lists.
    Route::get('/documentos/{codigo}/pdf', DescargarMiDocumento::class)->where('codigo', '[A-Za-z0-9-]{1,30}')->middleware('throttle:10,1')->name('api.estudiante.documentos.pdf');
});
