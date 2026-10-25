<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    $this->usuario = User::factory()->create(['email' => 'rosa@ciln.test']);
    $this->rosa = Persona::factory()->create(['user_id' => $this->usuario->id, 'apellido_paterno' => 'Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa', 'numero_documento' => '41234567', 'celular' => '987654321']);
    $this->otra = Persona::factory()->create();
    $this->token = $this->usuario->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
});

it('gives the student their own profile and refuses guests, accounts without a person record and unverified accounts', function (): void {
    $this->getJson('/api/v1/estudiante/perfil')->assertUnauthorized();

    $this->withToken($this->token)->getJson('/api/v1/estudiante/perfil')
        ->assertOk()
        ->assertExactJson(['data' => [
            'nombres' => 'Rosa', 'nombre_completo' => 'Quispe Mamani, Rosa', 'documento' => 'DNI 41234567',
            'correo' => $this->rosa->correo, 'celular' => '987654321',
        ]]);

    $personal = User::factory()->create();
    app('auth')->forgetGuards();
    $this->withToken($personal->createToken('App')->plainTextToken)->getJson('/api/v1/estudiante/perfil')
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu cuenta no está vinculada a una ficha de estudiante del CILN.');

    $sinVerificar = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $sinVerificar->id]);
    app('auth')->forgetGuards();
    $this->withToken($sinVerificar->createToken('App')->plainTextToken)->getJson('/api/v1/estudiante/perfil')
        ->assertForbidden()
        ->assertJsonPath('message', 'Verifica tu correo antes de usar estos datos.');
});

it('lists only the student\'s own enrollments, newest first, with their group, schedule, room or link and teacher', function (): void {
    $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027']);
    $ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 1, 'nivel' => NivelCurricular::Basico]);
    $grupo = Grupo::factory()->for($periodo)->for($ciclo)->create(['codigo' => 'ING-1-V', 'modalidad' => Modalidad::Virtual, 'enlace_virtual' => 'https://meet.test/ing-1-v']);
    [, $docente] = PortalDocente::docente();
    PortalDocente::asignar($grupo, $docente);
    $antigua = Matricula::factory()->retirada()->create(['persona_id' => $this->rosa->id, 'grupo_id' => Grupo::factory()->create()->id, 'matriculado_en' => now()->subMonth()]);
    $actual = Matricula::factory()->activa()->create(['persona_id' => $this->rosa->id, 'grupo_id' => $grupo->id, 'matriculado_en' => now()->subDay()]);
    Matricula::factory()->activa()->create(['persona_id' => $this->otra->id, 'grupo_id' => $grupo->id]);

    $respuesta = $this->withToken($this->token)->getJson('/api/v1/estudiante/matriculas')->assertOk();

    expect($respuesta->json('data.*.id'))->toBe([$actual->ulid, $antigua->ulid])
        ->and($respuesta->json('data.0'))->toMatchArray([
            'estado' => 'activa', 'estado_etiqueta' => 'Activa', 'condicion' => 'regular',
            'matriculado_en' => '2027-03-19T10:00:00-05:00',
            'docente' => app(DocentesDeGrupos::class)([$grupo->id])[$grupo->id],
        ])
        ->and($respuesta->json('data.0.grupo'))->toMatchArray([
            'codigo' => 'ING-1-V', 'idioma' => 'Inglés', 'ciclo' => 1, 'nivel' => 'Básico', 'modalidad' => 'Virtual',
            'periodo' => 'Marzo 2027', 'aula' => null, 'enlace_virtual' => 'https://meet.test/ing-1-v',
        ])
        ->and($respuesta->json('data.1.estado'))->toBe('retirada');
});

it('lists only the student\'s own payments, newest first, with their state and the reason of a rejection', function (): void {
    $concepto = Concepto::factory()->create(['nombre' => 'Derecho de matrícula']);
    $validado = Pago::factory()->validado()->for($concepto)->create(['persona_id' => $this->rosa->id, 'monto' => '80.00', 'fecha_pago' => '2027-03-10', 'medio_pago' => MedioPago::CajaPresencial, 'numero_operacion' => 'OP-1']);
    $rechazado = Pago::factory()->rechazado()->for($concepto)->create(['persona_id' => $this->rosa->id, 'monto' => '80.00', 'fecha_pago' => '2027-03-15', 'numero_operacion' => 'OP-2']);
    Pago::factory()->for($concepto)->create(['persona_id' => $this->otra->id, 'numero_operacion' => 'AJENO']);

    $respuesta = $this->withToken($this->token)->getJson('/api/v1/estudiante/pagos')->assertOk()->assertDontSee('AJENO');

    expect($respuesta->json('data.*.id'))->toBe([$rechazado->ulid, $validado->ulid])
        ->and($respuesta->json('data.1'))->toBe([
            'id' => $validado->ulid, 'concepto' => 'Derecho de matrícula', 'monto' => '80.00', 'fecha_pago' => '2027-03-10',
            'medio_pago' => 'caja_presencial', 'medio_pago_etiqueta' => MedioPago::CajaPresencial->etiqueta(), 'numero_operacion' => 'OP-1',
            'estado' => 'validado', 'estado_etiqueta' => 'Validado', 'motivo_rechazo' => null,
        ])
        ->and($respuesta->json('data.0.motivo_rechazo'))->toBe($rechazado->motivo_rechazo);
});
