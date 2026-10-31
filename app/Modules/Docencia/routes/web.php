<?php

declare(strict_types=1);

use App\Modules\Docencia\Livewire\AsistenciaDelGrupo;
use App\Modules\Docencia\Livewire\PreActaDelGrupo;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'docente'])->group(function (): void {
    Route::livewire('/docente/grupos/{grupo}/asistencia', AsistenciaDelGrupo::class)->whereNumber('grupo')->name('docente.asistencia');
    Route::livewire('/docente/grupos/{grupo}/notas', PreActaDelGrupo::class)->whereNumber('grupo')->name('docente.notas');
});
