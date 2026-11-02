<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Livewire\ExamenDeClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->conceptoExamen = Concepto::factory()->create(['nombre' => 'Examen de clasificación', 'tipo' => TipoConcepto::ExamenClasificacion]);
    $this->pago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-111']);
});

function portalClasificacionSolicitud(array $atributos = []): SolicitudClasificacion
{
    return SolicitudClasificacion::factory()->create([
        'persona_id' => test()->persona->id,
        'idioma_id' => test()->ingles->id,
        ...$atributos,
    ]);
}

function portalClasificacionPagoUsado(string $numeroOperacion): Pago
{
    $pago = Pago::factory()->validado()->for(test()->conceptoExamen)->create(['persona_id' => test()->persona->id, 'numero_operacion' => $numeroOperacion]);
    portalClasificacionSolicitud(['pago_id' => $pago->id, 'estado' => EstadoSolicitudClasificacion::Expirado]);

    return $pago;
}

it('lists only the student own requests, newest first, with language, state and dates', function (): void {
    $frances = Idioma::factory()->create(['nombre' => 'Francés']);
    portalClasificacionSolicitud([
        'idioma_id' => $frances->id,
        'estado' => EstadoSolicitudClasificacion::Expirado,
        'vence_en' => '2026-10-31',
        'created_at' => CarbonImmutable::parse('2026-10-01 21:00', 'America/Lima')->utc(),
    ]);
    portalClasificacionSolicitud(['vence_en' => '2026-11-23']);
    SolicitudClasificacion::factory()->create(['idioma_id' => $this->ingles->id, 'vence_en' => '2026-11-15']);

    $this->actingAs($this->user)
        ->get(route('estudiante.clasificacion'))
        ->assertOk()
        ->assertSeeInOrder(['Inglés', 'Pendiente de evaluación', '24/10/2026', '23/11/2026', 'Francés', 'Expirado', '01/10/2026'])
        ->assertDontSee('31/10/2026')
        ->assertDontSee('15/11/2026');
});

it('shows the exam date, score and assigned level of an evaluated request', function (): void {
    SolicitudClasificacion::factory()->evaluada()->create([
        'persona_id' => $this->persona->id,
        'idioma_id' => $this->ingles->id,
        'fecha_examen' => '2026-10-20',
        'puntaje' => '78.50',
        'ciclo_asignado_id' => $this->cicloVI->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(ExamenDeClasificacion::class)
        ->assertSeeInOrder(['Evaluado', 'Puntaje', '78.50', 'de 100', 'Empiezas en', 'Ciclo 6 (Intermedio)', 'Examen del', '20/10/2026']);
});

it('requests the exam with an unused validated placement payment and reloads the page', function (): void {
    Livewire::actingAs($this->user)
        ->test(ExamenDeClasificacion::class)
        ->set('idioma', (string) $this->ingles->id)
        ->set('pago', (string) $this->pago->id)
        ->call('solicitar')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.clasificacion'));

    expect(SolicitudClasificacion::query()->sole())
        ->persona_id->toBe($this->persona->id)
        ->idioma_id->toBe($this->ingles->id)
        ->pago_id->toBe($this->pago->id)
        ->estado->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and(session('estado'))->toBe('Registramos tu solicitud. La coordinación académica te indicará la fecha del examen.');
});

it('offers only unused validated placement payments', function (): void {
    $usado = portalClasificacionPagoUsado('OP-222');
    Pago::factory()->validado()->for(Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $this->persona->id, 'numero_operacion' => 'OP-333']);

    Livewire::actingAs($this->user)
        ->test(ExamenDeClasificacion::class)
        ->assertSee('OP-111')
        ->assertDontSee('OP-222')
        ->assertDontSee('OP-333')
        ->set('idioma', (string) $this->ingles->id)
        ->set('pago', (string) $usado->id)
        ->call('solicitar')
        ->assertHasErrors(['pago' => 'in']);
});

it('offers only active languages', function (): void {
    $quechua = Idioma::factory()->create(['nombre' => 'Quechua', 'activo' => false]);

    Livewire::actingAs($this->user)
        ->test(ExamenDeClasificacion::class)
        ->assertSee('Inglés')
        ->assertDontSee('Quechua')
        ->set('idioma', (string) $quechua->id)
        ->set('pago', (string) $this->pago->id)
        ->call('solicitar')
        ->assertHasErrors(['idioma' => 'in']);

    expect(SolicitudClasificacion::query()->exists())->toBeFalse();
});

it('refuses a tampered language or payment id without creating a request', function (): void {
    $ajeno = Pago::factory()->validado()->for($this->conceptoExamen)->create();
    $componente = Livewire::actingAs($this->user)->test(ExamenDeClasificacion::class);

    $componente->set('idioma', '999999')->set('pago', (string) $this->pago->id)->call('solicitar')->assertHasErrors(['idioma' => 'in']);
    $componente->set('idioma', (string) $this->ingles->id)->set('pago', (string) $ajeno->id)->call('solicitar')->assertHasErrors(['pago' => 'in']);

    expect(SolicitudClasificacion::query()->exists())->toBeFalse();
});

it('shows the business refusal and creates nothing', function (): void {
    portalClasificacionSolicitud();

    Livewire::actingAs($this->user)
        ->test(ExamenDeClasificacion::class)
        ->set('idioma', (string) $this->ingles->id)
        ->set('pago', (string) $this->pago->id)
        ->call('solicitar')
        ->assertHasErrors(['solicitud' => 'Ya tiene una solicitud pendiente de evaluación para Inglés.'])
        ->assertSee('Ya tiene una solicitud pendiente de evaluación para Inglés.')
        ->assertNoRedirect();

    expect(SolicitudClasificacion::query()->count())->toBe(1);
});

it('asks a student without an unused placement payment to register one', function (): void {
    $sinPago = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $sinPago->id]);
    Pago::factory()->pendiente()->for($this->conceptoExamen)->create(['persona_id' => $persona->id]);

    Livewire::actingAs($sinPago)
        ->test(ExamenDeClasificacion::class)
        ->assertSee('Aún no tienes un pago del examen de clasificación validado y sin usar.')
        ->assertSee(route('estudiante.pagos'))
        ->assertDontSee('Solicitar examen</button>', false);
});

it('sends guests of the placement test page to the portal sign-in', function (): void {
    $this->get(route('estudiante.clasificacion'))->assertRedirect(route('ingresar'));
});

it('refuses the placement test page to an account without a student record', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('estudiante.clasificacion'))
        ->assertForbidden();
});
