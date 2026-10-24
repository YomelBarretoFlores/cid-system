<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

it('logs a business rejection without the personal data it may mention', function (): void {
    Log::spy();

    RegistroDeRechazos::registrar(new ReglaDeNegocioIncumplida('Ya existe un docente con el RUC 10456789012, DNI 40123456 y correo Rosa.Q@unasam.edu.pe; grupo ING-05-A.'));

    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'Ya existe un docente con el RUC [dato], DNI [dato] y correo [correo]; grupo ING-05-A.'])->once();
});

it('logs the rejections shown in the panel', function (): void {
    $registros = [];
    Event::listen(MessageLogged::class, function (MessageLogged $mensaje) use (&$registros): void {
        $registros[] = [$mensaje->level, $mensaje->message, $mensaje->context];
    });
    $docente = Docente::factory()->create();
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);

    Livewire::actingAs($gestor)->test(ViewDocente::class, ['record' => $docente->getRouteKey()])
        ->callAction('cambiarEstado', data: ['estado' => 'activo'])
        ->assertNotified('El docente ya está Activo.');

    expect($registros)->toContain(['info', 'Regla de negocio incumplida.', ['regla' => 'El docente ya está Activo.']]);
});
