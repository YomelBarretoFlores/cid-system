<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->cicloUno = $this->grupo->ciclo;
    $this->cicloDos = Ciclo::factory()->create(['idioma_id' => $this->cicloUno->idioma_id, 'numero' => 2, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico II']);
    [$parcial, $final] = [$this->curso['parcial']->id, $this->curso['final']->id];
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo');
    $this->ciro = ActasFinales::estudiante($this->grupo, 'Castro');
    ActasFinales::preActa($this->ana, [$parcial => '15.00', $final => '14.00']);
    ActasFinales::preActa($this->beto, [$parcial => '8.00', $final => '10.00']);
});

function cerrarElActa(): void
{
    app(CerrarActa::class)(test()->grupo->id, test()->curso['docente']->id, test()->curso['usuario']);
}

/**
 * @return array<int, array{0: int, 1: string}>
 */
function habilitaciones(): array
{
    return Habilitacion::query()->orderBy('persona_id')->get()
        ->mapWithKeys(fn (Habilitacion $habilitacion): array => [$habilitacion->persona_id => [$habilitacion->ciclo_id, $habilitacion->origen->value]])
        ->all();
}

it('enables the next cycle only for the students who passed when the acta closes', function (): void {
    cerrarElActa();

    expect(habilitaciones())->toBe([$this->ana->persona_id => [$this->cicloDos->id, 'aprobacion']]);
});

it('never lowers a higher cycle the student already has, for example from the placement test', function (): void {
    $cicloTres = Ciclo::factory()->create(['idioma_id' => $this->cicloUno->idioma_id, 'numero' => 3, 'nivel' => NivelCurricular::Basico]);
    Habilitacion::factory()->create(['persona_id' => $this->ana->persona_id, 'idioma_id' => $this->cicloUno->idioma_id, 'ciclo_id' => $cicloTres->id]);

    cerrarElActa();

    expect(habilitaciones())->toBe([$this->ana->persona_id => [$cicloTres->id, 'clasificacion']]);
});

it('enables nothing after the last cycle of the language', function (): void {
    $this->cicloDos->delete();

    cerrarElActa();

    expect(habilitaciones())->toBe([]);
});

it('enables a student who passes after a rectification and keeps the cycle of one who no longer passes', function (): void {
    cerrarElActa();
    app(AutorizarRectificacion::class)(Acta::query()->sole()->id, 'Corrección de notas del examen final', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));
    $notas = fn (string $parcial, string $final): array => [$this->curso['parcial']->id => $parcial, $this->curso['final']->id => $final];
    app(RectificarNotasFinales::class)($this->grupo->id, $this->curso['docente']->id, [
        $this->ana->id => $notas('8.00', '8.00'),
        $this->ciro->id => $notas('16.00', '16.00'),
    ]);

    cerrarElActa();

    expect(habilitaciones())->toBe([
        $this->ana->persona_id => [$this->cicloDos->id, 'aprobacion'],
        $this->ciro->persona_id => [$this->cicloDos->id, 'aprobacion'],
    ]);
});

it('recomputes the cycles from every acta already closed, and running it again changes nothing', function (): void {
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Francés', [[1, '14.00', 'aprobado'], [2, '09.00', 'desaprobado']]);
    $cicloDosDeFrances = Ciclo::query()->where(['idioma_id' => Idioma::query()->latest('id')->value('id'), 'numero' => 2])->value('id');

    $this->artisan('matricula:recalcular-habilitaciones')->expectsOutput('Habilitaciones actualizadas: 1')->assertSuccessful();
    $this->artisan('matricula:recalcular-habilitaciones')->expectsOutput('Habilitaciones actualizadas: 0')->assertSuccessful();

    expect(habilitaciones())->toBe([$persona->id => [$cicloDosDeFrances, OrigenHabilitacion::Aprobacion->value]]);
});
