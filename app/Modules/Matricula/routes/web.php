<?php

declare(strict_types=1);

use App\Modules\Matricula\Http\Controllers\DescargarConstanciaDeInscripcion;
use App\Modules\Matricula\Http\Controllers\DescargarFichaDeMatricula;
use App\Modules\Matricula\Http\Controllers\DescargarFichaParaPersonal;
use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Livewire\MisMatriculas;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'verified', 'estudiante'])->group(function (): void {
    Route::livewire('/estudiante/matricula', Matricularse::class)->name('estudiante.matricula');
    Route::livewire('/estudiante/matriculas', MisMatriculas::class)->name('estudiante.matriculas');
    Route::get('/estudiante/matriculas/{matricula}/ficha', DescargarFichaDeMatricula::class)->name('estudiante.matriculas.ficha');
    Route::get('/estudiante/matriculas/{matricula}/constancia', DescargarConstanciaDeInscripcion::class)->name('estudiante.matriculas.constancia');
});

Route::get('/panel/matriculas/{matricula}/ficha', DescargarFichaParaPersonal::class)
    ->middleware(['auth', 'auth.session', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:view,matricula'])
    ->name('panel.matriculas.ficha');
