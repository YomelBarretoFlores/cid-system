<?php

declare(strict_types=1);

use App\Modules\Academico\Http\Controllers\Api\MostrarOferta;
use Illuminate\Support\Facades\Route;

Route::get('/oferta', MostrarOferta::class)->name('api.oferta');
