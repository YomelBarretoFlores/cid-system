<?php

declare(strict_types=1);

use App\Modules\Certificacion\Http\Controllers\Api\VerificarDocumentoPorApi;
use Illuminate\Support\Facades\Route;

Route::get('/documentos/{codigo}', VerificarDocumentoPorApi::class)
    ->where('codigo', '[A-Za-z0-9-]{1,30}')
    ->middleware('throttle:30,1')
    ->name('api.documentos.verificar');
