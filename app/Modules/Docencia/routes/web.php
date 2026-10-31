<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Http\Controllers\DescargarSilaboParaPersonal;
use App\Modules\Docencia\Livewire\ClasesPorConfirmar;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/clases', ClasesPorConfirmar::class)->name('estudiante.clases');
});

Route::get('/panel/silabos/{grupo}/pdf', DescargarSilaboParaPersonal::class)
    ->whereNumber('grupo')
    ->middleware(['auth', 'auth.session', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:'.PermisoDocencia::Ver->value])
    ->name('panel.silabos.pdf');
