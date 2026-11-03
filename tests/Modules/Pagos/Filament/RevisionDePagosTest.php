<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ViewPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->cajero = User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value,
    ]);
    $this->pendiente = Pago::factory()->create();
    $this->validado = Pago::factory()->validado()->create();
});

it('validates a pending payment from the list, recording the cashier', function (): void {
    $this->actingAs($this->cajero);

    Livewire::test(ListPagos::class)
        ->callAction(TestAction::make('validar')->table($this->pendiente), data: ['boleta_original' => true])
        ->assertNotified('Pago validado');

    expect($this->pendiente->refresh())
        ->estado->toBe(EstadoPago::Validado)
        ->validado_por->toBe($this->cajero->id);
});

it('rejects a pending payment from its page with a reason', function (): void {
    $this->actingAs($this->cajero);

    Livewire::test(ViewPago::class, ['record' => $this->pendiente->getRouteKey()])
        ->callAction('rechazar', data: ['motivo' => 'El recibo no es legible.'])
        ->assertHasNoFormErrors()
        ->assertNotified('Pago rechazado');

    expect($this->pendiente->refresh())
        ->estado->toBe(EstadoPago::Rechazado)
        ->motivo_rechazo->toBe('El recibo no es legible.');
});

it('requires a short reason to reject or void', function (string $accion, string $estado, string $motivo, string $regla): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoPago::valores()]));
    $pago = $this->{$estado};

    Livewire::test(ViewPago::class, ['record' => $pago->getRouteKey()])
        ->callAction($accion, data: ['motivo' => $motivo])
        ->assertHasFormErrors(['motivo' => $regla]);

    expect($pago->refresh()->estado->value)->toBe($estado === 'pendiente' ? 'pendiente' : 'validado');
})->with([
    'reject without reason' => ['rechazar', 'pendiente', '', 'required'],
    'void without reason' => ['anular', 'validado', '', 'required'],
    'reason too long' => ['rechazar', 'pendiente', str_repeat('x', 501), 'max'],
]);

it('offers each action only for the states it can change', function (): void {
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoPago::valores()]));

    Livewire::test(ListPagos::class)
        ->assertActionVisible(TestAction::make('validar')->table($this->pendiente))
        ->assertActionVisible(TestAction::make('rechazar')->table($this->pendiente))
        ->assertActionHidden(TestAction::make('anular')->table($this->pendiente))
        ->assertActionHidden(TestAction::make('validar')->table($this->validado))
        ->assertActionHidden(TestAction::make('rechazar')->table($this->validado))
        ->assertActionVisible(TestAction::make('anular')->table($this->validado));
});

it('lets only users with the void permission void a validated payment', function (): void {
    $this->actingAs($this->cajero);
    Livewire::test(ListPagos::class)->assertActionHidden(TestAction::make('anular')->table($this->validado));

    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Anular->value]));
    Livewire::test(ListPagos::class)
        ->assertActionHidden(TestAction::make('validar')->table($this->pendiente))
        ->callAction(TestAction::make('anular')->table($this->validado), data: ['motivo' => 'Pago duplicado.'])
        ->assertNotified('Pago anulado');

    expect($this->validado->refresh()->estado)->toBe(EstadoPago::Anulado);
});

it('records the panel user in the audit trail of every panel action', function (): void {
    $middleware = Route::getRoutes()->getByName('default-livewire.update')?->gatherMiddleware() ?? [];
    $grupoWeb = app('router')->getMiddlewareGroups()['web'];

    expect($middleware)->toContain('web')
        ->and($grupoWeb)->toContain(EstablecerContextoAuditoria::class);
});

function anuladorDePagos(): User
{
    return User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Anular->value]);
}

it('warns in the void dialog which enrollment, placement request or procedure the payment backs', function (): void {
    $ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $grupo = Grupo::factory()->for(Periodo::factory()->state(['nombre' => 'Enero 2027']))
        ->for(Ciclo::factory()->for($ingles)->state(['numero' => 1, 'nivel' => NivelCurricular::Basico]))->create();
    Matricula::factory()->create(['grupo_id' => $grupo->id, 'pago_id' => $this->validado->id]);
    $examen = Pago::factory()->validado()->create();
    SolicitudClasificacion::factory()->create(['idioma_id' => $ingles->id, 'pago_id' => $examen->id]);
    $tasa = Pago::factory()->validado()->create();
    $tramite = Tramite::factory()->create(['pago_id' => $tasa->id])->refresh();
    $this->actingAs(anuladorDePagos());

    Livewire::test(ViewPago::class, ['record' => $this->validado->getRouteKey()])
        ->mountAction('anular')
        ->assertMountedActionModalSee('Este pago respalda la matrícula en Inglés Básico (Enero 2027).');
    Livewire::test(ViewPago::class, ['record' => $examen->getRouteKey()])
        ->mountAction('anular')
        ->assertMountedActionModalSee('Este pago respalda la solicitud de examen de clasificación de Inglés.');
    Livewire::test(ViewPago::class, ['record' => $tasa->getRouteKey()])
        ->mountAction('anular')
        ->assertMountedActionModalSee("Este pago respalda el trámite {$tramite->codigo} ({$tramite->tipo_nombre}).");
});

it('shows no backing warning in the void dialog of a payment nothing uses', function (): void {
    $this->actingAs(anuladorDePagos());

    Livewire::test(ViewPago::class, ['record' => $this->validado->getRouteKey()])
        ->mountAction('anular')
        ->assertMountedActionModalDontSee('Este pago respalda');
});
