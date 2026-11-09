<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id, 'nombres' => 'Lucía']);
});

it('asks a student without a usable payment to register the enrollment payment', function (): void {
    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Aún no tienes una matrícula activa.', 'Te toca a ti', 'Registra tu pago de matrícula.', 'Registrar pago', 'Aún no estudias ningún idioma en el CILN.', 'Esta semana', 'Aún no tienes clases.']);
});

it('shows an enrolled student the group, the weekly classes and the cycle in progress', function (): void {
    $grupo = Grupo::factory()
        ->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->state(['numero' => 2]))
        ->create(['aula_id' => Aula::factory()->create(['codigo' => 'A-204'])->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Tu matrícula está validada, todo está en orden.', 'Estás matriculado en Inglés · Ciclo 2', 'Ver ficha de matrícula', 'Cursando el ciclo 2', 'Esta semana', 'Mar', 'Inglés · 07:00 · A-204']);
});

it('points a teacher without any enrollment to the groups instead of asking for an enrollment payment', function (): void {
    [$docente] = PortalDocente::docente();

    $this->actingAs($docente)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Tus grupos están en Mis grupos.')
        ->assertSee(route('filament.docente.pages.mis-grupos'))
        ->assertDontSee('Registra tu pago de matrícula.')
        ->assertDontSee('Tu horario semanal');
});

it('treats an enrollment whose period already ended as a finished cycle, not as current classes', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'America/Lima'));
    $pasado = Periodo::factory()->create(['nombre' => 'Enero 2027', 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-31', 'matricula_inicio' => '2026-12-20', 'matricula_fin' => '2027-01-03']);
    $grupo = Grupo::factory()->for($pasado)
        ->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Portugués']))->state(['numero' => 2]))
        ->create(['aula_id' => Aula::factory()->create(['codigo' => 'P-301'])->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Registra tu pago de matrícula.')
        ->assertSee('Aún no tienes clases.')
        ->assertSee('Terminó el ciclo 2 en Enero 2027')
        ->assertDontSee('P-301')
        ->assertDontSee('Cursando el ciclo 2');
});

it('keeps an enrollment current on the last day of its period, in Lima', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-01-31 18:00', 'America/Lima'));
    $periodo = Periodo::factory()->create(['nombre' => 'Enero 2027', 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-31', 'matricula_inicio' => '2026-12-20', 'matricula_fin' => '2027-01-03']);
    $grupo = Grupo::factory()->for($periodo)->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Portugués']))->state(['numero' => 2]))->create();
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Estás matriculado en Portugués · Ciclo 2')
        ->assertSee('Cursando el ciclo 2');
});

it('asks the student to sit a pending placement exam before it expires, and drops it once it expired', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00', 'America/Lima'));
    SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => Idioma::factory()->create(['nombre' => 'Quechua'])->id, 'vence_en' => '2027-03-20']);
    SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => Idioma::factory()->create(['nombre' => 'Francés'])->id, 'vence_en' => '2027-03-09']);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Te toca a ti', 'Registra tu pago de matrícula.', 'Rinde tu examen de clasificación de Quechua.', 'Acércate al CILN antes del 20/03/2027.', 'Ver clasificación'])
        ->assertSee(route('estudiante.clasificacion'))
        ->assertDontSee('clasificación de Francés');
});

it('tells the student which procedures the CILN is reviewing', function (): void {
    Tramite::factory()->count(2)->create(['persona_id' => $this->persona->id]);
    Tramite::factory()->create(['persona_id' => $this->persona->id, 'estado' => EstadoTramite::PendientePago]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Le toca al CILN', 'Tus 2 trámites están en revisión.', 'Te avisaremos por correo cuando avancen.', 'En revisión', 'Ver mis trámites']);
});

it('puts the enrollment deadline and the acta window of this week on their days', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 10:00', 'America/Lima'));
    $actual = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'fecha_inicio' => '2027-02-01', 'fecha_fin' => '2027-03-31', 'actas_inicio' => '2027-03-11', 'actas_fin' => '2027-03-14']);
    Periodo::factory()->create(['nombre' => 'Abril 2027', 'estado' => EstadoPeriodo::MatriculaAbierta, 'matricula_inicio' => '2027-03-01', 'matricula_fin' => '2027-03-12', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);
    $grupo = Grupo::factory()->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés'])))->create(['periodo_id' => $actual->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Esta semana', 'Lun', 'Inglés · 07:00', 'Jue', 'Empieza el cierre de actas de Marzo 2027', 'Vie', 'Cierra la matrícula de Abril 2027', 'Dom', 'Último día del cierre de actas de Marzo 2027']);
});
