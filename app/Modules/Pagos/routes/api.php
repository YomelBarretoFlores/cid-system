<?php

declare(strict_types=1);

use App\Modules\Pagos\Http\Controllers\Api\ListarTarifas;
use Illuminate\Support\Facades\Route;

Route::get('/tarifas', ListarTarifas::class)->name('api.tarifas');
