<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $this->token = $this->usuario->createToken('App', expiresAt: now()->addDays(30))->plainTextToken;
    $ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 5, 'nivel' => NivelCurricular::Intermedio]);
    $this->marzo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-31']);
    $this->abril = Periodo::factory()->create(['nombre' => 'Abril 2027', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);
    $this->grupoDeMarzo = Grupo::factory()->for($this->marzo)->for($ciclo)->create(['codigo' => 'ING-5-M']);
    $this->grupoDeAbril = Grupo::factory()->for($this->abril)->for($ciclo)->create(['codigo' => 'ING-5-T', 'estado' => EstadoGrupo::Programado]);
    $finalizado = Grupo::factory()->for($this->marzo)->for($ciclo)->create(['codigo' => 'ING-5-F', 'estado' => EstadoGrupo::Finalizado]);
    foreach ([$this->grupoDeMarzo, $this->grupoDeAbril, $finalizado] as $grupo) {
        PortalDocente::asignar($grupo, $this->docente);
    }
    [, $otroDocente] = PortalDocente::docente();
    PortalDocente::asignar(Grupo::factory()->for($this->marzo)->for($ciclo)->create(['codigo' => 'ING-5-AJENO']), $otroDocente);
    DB::table('aca_horarios')->insert([
        ['grupo_id' => $this->grupoDeMarzo->id, 'dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '09:00', 'created_at' => now(), 'updated_at' => now()],
        ['grupo_id' => $this->grupoDeMarzo->id, 'dia_semana' => 3, 'hora_inicio' => '07:00', 'hora_fin' => '09:00', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('lists the teacher\'s own current groups, latest period first, with their schedule', function (): void {
    $respuesta = $this->withToken($this->token)->getJson('/api/v1/docente/grupos')->assertOk()->assertDontSee('ING-5-AJENO')->assertDontSee('ING-5-F');

    expect($respuesta->json('data.*.codigo'))->toBe(['ING-5-T', 'ING-5-M'])
        ->and($respuesta->json('data.1'))->toMatchArray([
            'codigo' => 'ING-5-M', 'estado' => 'abierto', 'idioma' => 'Inglés', 'nivel' => 'Intermedio', 'ciclo' => 5,
            'periodo' => 'Marzo 2027', 'periodo_inicio' => '2027-03-01', 'periodo_fin' => '2027-03-31',
            'sesiones' => [['dia' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '09:00'], ['dia' => 3, 'hora_inicio' => '07:00', 'hora_fin' => '09:00']],
        ]);
});

it('refuses guests, accounts without a teacher profile and unverified teachers', function (): void {
    $this->getJson('/api/v1/docente/grupos')->assertUnauthorized();

    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);
    $this->withToken($estudiante->createToken('App')->plainTextToken)->getJson('/api/v1/docente/grupos')
        ->assertForbidden()
        ->assertJsonPath('message', 'Tu cuenta no está vinculada a un perfil docente del CILN.');

    app('auth')->forgetGuards();
    $this->usuario->forceFill(['email_verified_at' => null])->save();
    $this->withToken($this->token)->getJson('/api/v1/docente/grupos')
        ->assertForbidden()
        ->assertJsonPath('message', 'Verifica tu correo antes de usar estos datos.');
});
