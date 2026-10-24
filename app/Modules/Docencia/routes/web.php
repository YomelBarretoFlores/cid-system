<?php

declare(strict_types=1);

use App\Modules\Docencia\Livewire\AsistenciaDelGrupo;
use App\Modules\Docencia\Livewire\MisGrupos;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'docente'])->group(function (): void {
    Route::livewire('/docente', MisGrupos::class)->name('docente.grupos');
    Route::livewire('/docente/grupos/{grupo}/asistencia', AsistenciaDelGrupo::class)->whereNumber('grupo')->name('docente.asistencia');
});
