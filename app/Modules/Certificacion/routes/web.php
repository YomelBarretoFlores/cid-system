<?php

declare(strict_types=1);

use App\Modules\Certificacion\Http\Controllers\DescargarActaFinalDelDocente;
use App\Modules\Certificacion\Http\Controllers\DescargarActaFinalParaPersonal;
use App\Modules\Certificacion\Http\Controllers\DescargarDocumentoDelEstudiante;
use App\Modules\Certificacion\Http\Controllers\DescargarDocumentoParaPersonal;
use App\Modules\Certificacion\Http\Controllers\DescargarPreActa;
use App\Modules\Certificacion\Http\Controllers\VerificarActa;
use App\Modules\Certificacion\Http\Controllers\VerificarDocumento;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'auth.session', 'docente'])->group(function (): void {
    Route::get('/docente/grupos/{grupo}/acta/pdf', DescargarActaFinalDelDocente::class)->whereNumber('grupo')->name('docente.acta.pdf');
    Route::get('/docente/grupos/{grupo}/pre-acta/pdf', DescargarPreActa::class)->whereNumber('grupo')->name('docente.preacta.pdf');
});

Route::get('/estudiante/documentos/{documento}/pdf', DescargarDocumentoDelEstudiante::class)
    ->whereNumber('documento')
    ->middleware(['auth', 'auth.session', 'estudiante', 'estudiante.habilitado'])
    ->name('estudiante.documentos.pdf');

Route::get('/panel/actas-finales/{acta}/pdf', DescargarActaFinalParaPersonal::class)
    ->middleware(['auth', 'auth.session', 'panel.sesion', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:view,acta'])
    ->name('panel.actas.pdf');

Route::get('/panel/grupos/{grupo}/pre-acta/pdf', DescargarPreActa::class)
    ->whereNumber('grupo')
    ->middleware(['auth', 'auth.session', 'panel.sesion', 'can:'.User::ADMIN_PANEL_PERMISSION])
    ->name('panel.grupos.preacta.pdf');

Route::get('/panel/documentos/{documento}/pdf', DescargarDocumentoParaPersonal::class)
    ->middleware(['auth', 'auth.session', 'panel.sesion', 'can:'.User::ADMIN_PANEL_PERMISSION, 'can:view,documento'])
    ->name('panel.documentos.pdf');

Route::get('/verificar/acta/{acta}', VerificarActa::class)
    ->whereNumber('acta')
    ->middleware(['throttle:30,1', 'signed:relative'])
    ->name('verificacion.acta');

Route::get('/verificar/{codigo?}', VerificarDocumento::class)
    ->where('codigo', '[A-Za-z0-9-]{1,30}')
    ->middleware('throttle:30,1')
    ->name('verificacion.documento');
