<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\EvaluarRequisitosDeMatricula;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Data\RequisitoDeMatricula;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\TipoDeRequisito;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'America/Lima'));
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->idioma = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->ciclo1 = Ciclo::factory()->for($this->idioma)->create(['numero' => 1, 'nivel' => NivelCurricular::Basico]);
    $this->ciclo2 = Ciclo::factory()->for($this->idioma)->create(['numero' => 2, 'nivel' => NivelCurricular::Basico]);
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create(['nombre' => 'Marzo 2027']);
    $this->grupo = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create(['codigo' => 'ING-1-A', 'cupo_maximo' => 20]);
    $this->concepto = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
    $this->pago = Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id]);
});

/**
 * @return array<string, array{0: bool, 1: list<string>}>
 */
function requisitosDelEstudiante(): array
{
    return collect(app(EvaluarRequisitosDeMatricula::class)(test()->persona->id))
        ->mapWithKeys(fn (RequisitoDeMatricula $requisito): array => [$requisito->titulo => [$requisito->cumplido, $requisito->detalle]])
        ->all();
}

function intentarMatricula(Grupo $grupo, ?Pago $pago = null): Matricula
{
    return app(MatricularEstudiante::class)(test()->persona->id, $grupo->id, ($pago ?? test()->pago)->id);
}

it('lists every requirement as met, with its reason, when the student can enroll', function (): void {
    expect(requisitosDelEstudiante())->toBe([
        'Periodo de matrícula abierto' => [true, ['Marzo 2027: del 7 mar 2027 al 13 mar 2027.']],
        'Ciclo que te corresponde' => [true, ['En todos los idiomas empiezas en el ciclo 1, o puedes rendir el examen de clasificación.']],
        'Pago de matrícula validado y sin usar' => [true, ['Tienes 1 pago de matrícula disponible.']],
        'Grupos de tu ciclo con vacantes' => [true, ['Inglés: 1 grupo con vacantes.']],
        'Sin otra matrícula en el mismo ciclo y periodo' => [true, ['No tienes otra matrícula activa en el ciclo que te corresponde de este periodo.']],
    ]);
});

it('explains when the enrollment period has not opened yet or none is scheduled', function (): void {
    $this->periodo->update(['estado' => EstadoPeriodo::Planificado, 'matricula_inicio' => '2027-03-15', 'matricula_fin' => '2027-03-20']);

    expect(requisitosDelEstudiante()['Periodo de matrícula abierto'])->toBe([false, ['La matrícula del periodo Marzo 2027 abre el 15 mar 2027.']]);

    $this->periodo->update(['matricula_inicio' => '2027-03-01', 'matricula_fin' => '2027-03-09']);

    expect(requisitosDelEstudiante()['Periodo de matrícula abierto'])->toBe([false, ['Aún no hay un periodo de matrícula programado.']])
        ->and(fn (): Matricula => intentarMatricula($this->grupo))->toThrow(ReglaDeNegocioIncumplida::class);
});

it('shows the enabled cycle of each language with its origin, and that a cycle without a closed acta is still being taken', function (): void {
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->idioma->id, 'ciclo_id' => $this->ciclo2->id, 'origen' => OrigenHabilitacion::Aprobacion]);
    $pasado = Periodo::factory()->create(['nombre' => 'Enero 2027']);
    Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->for($pasado)->for($this->ciclo2)->create()->id]);
    $grupoDos = Grupo::factory()->for($this->periodo)->for($this->ciclo2)->create(['codigo' => 'ING-2-A']);

    expect(requisitosDelEstudiante()['Ciclo que te corresponde'])->toBe([true, [
        'Inglés: Ciclo 2 (Básico), por aprobación del ciclo anterior; lo estás cursando: si lo apruebas, al cerrarse el acta se habilitará el ciclo siguiente.',
        'En los demás idiomas empiezas en el ciclo 1, o puedes rendir el examen de clasificación.',
    ]])
        ->and(intentarMatricula($grupoDos)->condicion)->toBe(CondicionMatricula::Repitente);
});

it('says the student would repeat a cycle already graded in a closed acta', function (): void {
    ActasFinales::historial($this->persona, 'Francés', [[1, '09.00', 'desaprobado']]);
    $frances = Idioma::query()->latest('id')->firstOrFail();

    expect(requisitosDelEstudiante()['Ciclo que te corresponde'][1])->toContain("{$frances->nombre}: Ciclo 1 (Básico), desde el inicio del programa; lo llevarías como repitente.");
});

it('asks for a payment when there is no validated one left to use, and the enrollment refuses too', function (): void {
    Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'pago_id' => $this->pago->id, 'grupo_id' => Grupo::factory()->create()->id]);

    expect(requisitosDelEstudiante()['Pago de matrícula validado y sin usar'])->toBe([false, ['Registra tu pago de matrícula y espera a que el CILN lo valide.']])
        ->and(fn (): Matricula => intentarMatricula($this->grupo))->toThrow(ReglaDeNegocioIncumplida::class);
});

it('tells apart a cycle with full groups from a cycle with no open groups', function (): void {
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['matriculados' => 20]);

    expect(requisitosDelEstudiante()['Grupos de tu ciclo con vacantes'])->toBe([false, ['Los grupos abiertos de tu ciclo ya no tienen vacantes.']])
        ->and(fn (): Matricula => intentarMatricula($this->grupo))->toThrow(ReglaDeNegocioIncumplida::class);

    $this->grupo->delete();

    expect(requisitosDelEstudiante()['Grupos de tu ciclo con vacantes'])->toBe([false, ['Todavía no hay grupos abiertos para tu ciclo.']]);
});

it('flags an active enrollment in the same cycle and period, which the enrollment refuses, and leaves those groups out of the vacancies', function (): void {
    intentarMatricula($this->grupo);
    $otro = Grupo::factory()->for($this->periodo)->for($this->ciclo1)->create(['codigo' => 'ING-1-B']);
    $segundoPago = Pago::factory()->validado()->for($this->concepto)->create(['persona_id' => $this->persona->id]);

    expect(requisitosDelEstudiante()['Sin otra matrícula en el mismo ciclo y periodo'])->toBe([false, ['Ya estás matriculado en Inglés, ciclo 1, de este periodo (grupo ING-1-A).']])
        ->and(requisitosDelEstudiante()['Grupos de tu ciclo con vacantes'][0])->toBeFalse()
        ->and(fn (): Matricula => intentarMatricula($otro, $segundoPago))->toThrow(ReglaDeNegocioIncumplida::class, 'Ya tiene una matrícula activa en este ciclo y periodo.');
});

it('shows the status heading and the checklist above the offer on the enrollment page', function (): void {
    $sinPago = User::factory()->create();
    Persona::factory()->create(['user_id' => $sinPago->id]);

    $this->actingAs($sinPago)
        ->get(route('estudiante.matricula'))
        ->assertOk()
        ->assertSeeInOrder(['No puedes matricularte este periodo', 'Periodo de matrícula abierto', 'Pago de matrícula validado y sin usar', 'Registra tu pago de matrícula y espera a que el CILN lo valide.', 'Elige tu grupo'])
        ->assertSee(route('estudiante.pagos'))
        ->assertSee('Cumplido')
        ->assertSee('Pendiente');
});

it('names each requirement by its type so the portal never reads them by position', function (): void {
    $tipos = collect(app(EvaluarRequisitosDeMatricula::class)($this->persona->id))->map(fn (RequisitoDeMatricula $requisito): TipoDeRequisito => $requisito->tipo)->all();

    expect($tipos)->toEqualCanonicalizing(TipoDeRequisito::cases())
        ->and(array_unique(array_map(fn (TipoDeRequisito $tipo): string => $tipo->value, $tipos)))->toHaveCount(count(TipoDeRequisito::cases()));
});
