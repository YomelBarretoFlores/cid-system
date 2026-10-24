<?php

declare(strict_types=1);

use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Livewire\MisMatriculas;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/matricula', Matricularse::class)->name('estudiante.matricula');
    Route::livewire('/estudiante/matriculas', MisMatriculas::class)->name('estudiante.matriculas');
});
