<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'America/Lima'));
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id, 'nombres' => 'Lucía', 'tipo_vinculo' => TipoVinculo::EstudianteUnasam]);
    $this->matricula = Concepto::factory()->create(['nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula]);
    Tarifa::factory()->for($this->matricula)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'monto' => '120.00']);
    Tarifa::factory()->for($this->matricula)->create(['tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '150.00']);
});

function pagoDeLucia(string $estado, string $fecha, ?Concepto $concepto = null): Pago
{
    return Pago::factory()->{$estado}()->for($concepto ?? test()->matricula)->create(['persona_id' => test()->persona->id, 'fecha_pago' => $fecha]);
}

it('tells a student whose payment is waiting for validation to wait instead of paying again', function (): void {
    pagoDeLucia('pendiente', '2027-03-09');

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Tu pago de matrícula está por validar.', 'Cuando el CILN lo valide podrás elegir tu grupo.', 'Ver registro de pagos', 'Tus pagos', 'Por validar', '1'])
        ->assertDontSee('Registra tu pago de matrícula.');
});

it('shows the reason when the latest payment was rejected and asks for a new one', function (): void {
    pagoDeLucia('rechazado', '2027-03-09');

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Tu último pago fue rechazado.', 'El número de operación no coincide con el recibo.', 'Registrar pago']);
});

it('stops showing an old rejection once a newer payment exists', function (): void {
    pagoDeLucia('rechazado', '2027-03-01');
    pagoDeLucia('validado', '2027-03-05');

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Tu pago de matrícula ya fue validado.')
        ->assertDontSee('El número de operación no coincide con el recibo.');
});

it('summarizes the payments and the enrollment fee for the student link', function (): void {
    pagoDeLucia('validado', '2027-03-05');
    pagoDeLucia('validado', '2027-03-06', Concepto::factory()->create(['tipo' => TipoConcepto::Constancia]));
    pagoDeLucia('pendiente', '2027-03-07', Concepto::factory()->create(['tipo' => TipoConcepto::Constancia]));

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Tus pagos', 'Por validar', '1', 'Validados', '2', 'Para matrícula', '1', 'Matrícula: S/ 120.00 según tu vínculo'])
        ->assertSee(route('estudiante.pagos'));
});

it('links the documents of the current enrollment and the trámites page', function (): void {
    $matricula = Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create()->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Documentos', 'Ficha de matrícula', 'Constancia de inscripción', 'Horario personalizado', 'Boleta de notas', 'Asistencia', 'Enlaces rápidos', 'Trámites', 'Constancias y certificados'])
        ->assertSee(route('estudiante.reportes.asistencia', $matricula->ulid))
        ->assertSee(route('estudiante.matriculas.ficha', $matricula->ulid))
        ->assertSee(route('estudiante.matriculas.constancia', $matricula->ulid))
        ->assertSee(route('estudiante.tramites'));
});

it('keeps only the trámites link while the student has no active enrollment', function (): void {
    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Documentos')
        ->assertSee('Tus documentos aparecerán cuando te matricules.')
        ->assertSee(route('estudiante.tramites'))
        ->assertDontSee('Ficha de matrícula');
});

it('tells a student with a validated payment until when they can choose a group', function (): void {
    pagoDeLucia('validado', '2027-03-05');
    Periodo::factory()->create(['nombre' => 'Abril 2027', 'estado' => EstadoPeriodo::MatriculaAbierta, 'matricula_inicio' => '2027-03-01', 'matricula_fin' => '2027-03-20', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Tu pago de matrícula ya fue validado.', 'Elige tu grupo de Abril 2027 hasta el 20 de marzo.', 'Elegir mi grupo']);
});
