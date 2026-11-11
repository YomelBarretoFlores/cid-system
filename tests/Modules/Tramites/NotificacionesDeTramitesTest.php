<?php

declare(strict_types=1);

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\ObservarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\EstadoNotificacion;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Listeners\NotificarCambioDeTramite;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\Notificacion;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\CorreoDeCambioDeTramite;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create(['nombres' => 'Lucía', 'correo' => 'lucia.ramirez@correo.test']);
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    $this->personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);
});

function retiroDe(Persona $persona, Matricula $matricula): Tramite
{
    return app(SolicitarTramite::class)($persona->id, TipoTramite::query()->where('codigo', 'RETIRO')->value('id'), new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de horario de trabajo'));
}

it('e-mails the student on each change and logs the delivery with its date', function (): void {
    Mail::fake();
    $this->travelTo(now()->setTime(15, 30));

    $tramite = retiroDe($this->persona, $this->matricula);
    app(ObservarTramite::class)($tramite->id, $this->personal, 'Indica desde qué fecha dejas de asistir.');

    Mail::assertSent(CorreoDeCambioDeTramite::class, 2);
    Mail::assertSent(CorreoDeCambioDeTramite::class, fn (CorreoDeCambioDeTramite $correo): bool => $correo->hasTo('lucia.ramirez@correo.test')
        && $correo->envelope()->subject === "Tu trámite {$tramite->refresh()->codigo}: Observado");
    expect(Notificacion::query()->where('tramite_id', $tramite->id)->orderBy('id')->get())
        ->toHaveCount(2)
        ->each(fn ($notificacion) => $notificacion->estado->toBe(EstadoNotificacion::Enviado)->intentos->toBe(1)->enviado_en->not->toBeNull()->destinatario->toBe('lucia.ramirez@correo.test'));
});

it('writes the e-mail in Spanish with the trámite, its state, stage, comment and a link to the portal', function (): void {
    $tramite = retiroDe($this->persona, $this->matricula);
    app(ObservarTramite::class)($tramite->id, $this->personal, 'Indica desde qué fecha dejas de asistir.');
    $movimiento = Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->firstOrFail();

    expect(new CorreoDeCambioDeTramite($tramite->refresh(), $movimiento, 'Lucía')->render())
        ->toContain('Hola, Lucía')
        ->toContain('Retiro del ciclo')
        ->toContain($tramite->codigo)
        ->toContain('Observado')
        ->toContain('Indica desde qué fecha dejas de asistir.')
        ->toContain(route('estudiante.tramites'));
});

it('logs a failed delivery when the person has no e-mail, without sending anything', function (): void {
    Mail::fake();
    $sinCorreo = Persona::factory()->create(['correo' => null]);

    $tramite = retiroDe($sinCorreo, Matricula::factory()->activa()->create(['persona_id' => $sinCorreo->id]));

    Mail::assertNothingSent();
    expect(Notificacion::query()->where('tramite_id', $tramite->id)->sole())
        ->estado->toBe(EstadoNotificacion::Fallido)->error->toBe('La persona no tiene correo registrado.');
});

it('marks the log as failed when the mail server keeps failing, and never resends a delivered e-mail', function (): void {
    Mail::fake();
    $tramite = retiroDe($this->persona, $this->matricula);
    $evento = new TramiteActualizado($tramite->id, Movimiento::query()->where('tramite_id', $tramite->id)->value('id'));
    $listener = app(NotificarCambioDeTramite::class);

    $listener->handle($evento);
    Mail::assertSent(CorreoDeCambioDeTramite::class, 1);

    Notificacion::query()->where('tramite_id', $tramite->id)->update(['estado' => 'pendiente']);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Conexión rechazada por el servidor de correo'));
    expect(fn () => $listener->handle($evento))->toThrow(RuntimeException::class);
    $listener->failed($evento, new RuntimeException('Conexión rechazada por el servidor de correo'));

    expect(Notificacion::query()->where('tramite_id', $tramite->id)->sole())->estado->toBe(EstadoNotificacion::Fallido)->error->toBe('RuntimeException')->intentos->toBe(2)
        ->and($tramite->refresh()->estado)->toBe(EstadoTramite::EnProceso);
});

it('shows the e-mail log on the trámite page', function (): void {
    Mail::fake();
    $tramite = retiroDe($this->persona, $this->matricula);

    Livewire::actingAs($this->personal)->test(ViewTramite::class, ['record' => $tramite->getRouteKey()])
        ->assertSeeText('Correos enviados')
        ->assertSeeText('lucia.ramirez@correo.test')
        ->assertSeeText("Tu trámite {$tramite->refresh()->codigo}: En proceso")
        ->assertSeeText('Enviado');
});
