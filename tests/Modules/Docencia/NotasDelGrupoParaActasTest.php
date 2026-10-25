<?php

declare(strict_types=1);

use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Data\DocenteAsignado;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->grupo = Grupo::factory()->create();
    $this->esquema = EsquemaEvaluacion::factory()->create();
    $this->parcial = ComponenteEvaluacion::factory()->for($this->esquema, 'esquema')->create(['codigo' => 'parcial']);
    $this->final = ComponenteEvaluacion::factory()->for($this->esquema, 'esquema')->create(['codigo' => 'final']);
});

function notaParcialDe(Matricula $matricula, ComponenteEvaluacion $componente, ?string $nota): void
{
    NotaParcial::query()->create(['grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id, 'componente_id' => $componente->id, 'nota' => $nota, 'registrado_por' => User::factory()->create()->id]);
}

it('gives the recorded partial grades of a group per enrollment and component, leaving blanks out', function (): void {
    $ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id]);
    $luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id]);
    notaParcialDe($ana, $this->parcial, '15.50');
    notaParcialDe($ana, $this->final, null);
    notaParcialDe($luis, $this->final, '11.00');
    notaParcialDe(Matricula::factory()->create(), $this->parcial, '20.00');

    expect(app(NotasParcialesDelGrupo::class)($this->grupo->id))->toBe([
        $ana->id => [$this->parcial->id => '15.50'],
        $luis->id => [$this->final->id => '11.00'],
    ]);
});

it('gives the teacher assigned to a group, or nothing when it has none', function (): void {
    [, $docente] = PortalDocente::docente();
    Persona::query()->whereKey($docente->persona_id)->update(['apellido_paterno' => 'Vega', 'apellido_materno' => 'Ríos', 'nombres' => 'Carlos']);
    PortalDocente::asignar($this->grupo, $docente);

    expect(app(DocenteDelGrupo::class)($this->grupo->id))->toEqual(new DocenteAsignado($docente->id, 'Vega Ríos, Carlos'))
        ->and(app(DocenteDelGrupo::class)(Grupo::factory()->create()->id))->toBeNull();
});
