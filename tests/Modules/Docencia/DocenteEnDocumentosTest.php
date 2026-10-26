<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    Pdf::fake();
    $ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Inglés']))->create(['numero' => 2, 'nivel' => NivelCurricular::Basico]);
    $periodo = Periodo::factory()->create(['nombre' => 'Enero 2027']);
    $this->conDocente = Grupo::factory()->create(['codigo' => 'ING-02-A', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    $this->sinDocente = Grupo::factory()->create(['codigo' => 'ING-02-B', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    $persona = Persona::factory()->create(['apellido_paterno' => 'Huamán', 'apellido_materno' => 'Torres', 'nombres' => 'Julio César']);
    $docente = Docente::factory()->create(['persona_id' => $persona->id]);
    CargaAcademica::query()->create(['grupo_id' => $this->conDocente->id, 'docente_id' => $docente->id, 'asignado_por' => User::factory()->create()->id]);
});

function matriculaEn(Grupo $grupo): array
{
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);

    return [$user, Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id])];
}

it('names the assigned teacher of each group and nothing for groups without one', function (): void {
    expect(app(DocentesDeGrupos::class)([$this->conDocente->id, $this->sinDocente->id]))
        ->toBe([$this->conDocente->id => 'Huamán Torres, Julio César']);
});

it('shows the teacher on the enrollment record', function (Closure $grupo, string $docente): void {
    [$user, $matricula] = matriculaEn($grupo());

    $this->actingAs($user)->get(route('estudiante.matriculas.ficha', $matricula))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains("Docente: {$docente} ·"));
})->with([
    'assigned' => [fn (): Grupo => test()->conDocente, 'Huamán Torres, Julio César'],
    'not yet assigned' => [fn (): Grupo => test()->sinDocente, 'Por asignar'],
]);

it('shows the teacher on the student enrollments page', function (): void {
    [$user] = matriculaEn($this->conDocente);

    $this->actingAs($user)->get(route('estudiante.matriculas'))
        ->assertOk()
        ->assertSee(['Docente', 'Huamán Torres, Julio César']);
});

it('shows the teacher in the group list of the panel', function (): void {
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);

    Livewire::actingAs($gestor)->test(ListGrupos::class)
        ->assertTableColumnFormattedStateSet('docente', 'Huamán Torres, Julio César', $this->conDocente)
        ->assertTableColumnFormattedStateSet('docente', 'Por asignar', $this->sinDocente);
});
