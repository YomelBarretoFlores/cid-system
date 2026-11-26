<?php

declare(strict_types=1);

use App\Modules\Docencia\Livewire\ClasesPorConfirmar;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'estudiante', 'estudiante.habilitado'])->group(function (): void {
    Route::livewire('/estudiante/clases', ClasesPorConfirmar::class)->name('estudiante.clases');
});
