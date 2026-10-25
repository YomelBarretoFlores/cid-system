<?php

declare(strict_types=1);

use App\Shared\Auth\Http\Controllers\Api\EmitirToken;
use App\Shared\Auth\Http\Controllers\Api\MostrarSesion;
use App\Shared\Auth\Http\Controllers\Api\RevocarToken;
use Illuminate\Support\Facades\Route;

Route::post('/sesiones', EmitirToken::class)->name('api.sesiones.emitir');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/sesiones/actual', MostrarSesion::class)->name('api.sesiones.actual');
    Route::delete('/sesiones/actual', RevocarToken::class)->name('api.sesiones.revocar');
});
