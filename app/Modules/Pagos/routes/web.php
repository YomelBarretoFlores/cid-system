<?php

declare(strict_types=1);

use App\Modules\Pagos\Http\Controllers\VerReciboPropio;
use App\Modules\Pagos\Livewire\MisPagos;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/pagos', MisPagos::class)->name('estudiante.pagos');
    Route::get('/estudiante/pagos/{pago:ulid}/recibo', VerReciboPropio::class)->name('estudiante.pagos.recibo');
});
