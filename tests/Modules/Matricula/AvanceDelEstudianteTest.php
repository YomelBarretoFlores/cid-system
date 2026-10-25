<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\ResumirAvanceDelEstudiante;
use App\Modules\Matricula\Data\AvanceEnIdioma;
use App\Modules\Matricula\Data\ClaseDeLaSemana;
use App\Modules\Matricula\Enums\PasoDelEstudiante;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->conceptoMatricula = Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]);
});

function grupoDeAvance(Idioma $idioma, int $ciclo, string $periodo, string $aula): Grupo
{
    return Grupo::factory()
        ->for(Ciclo::factory()->for($idioma)->state(['numero' => $ciclo]))
        ->for(Periodo::factory()->state(['nombre' => $periodo]))
        ->create(['aula_id' => Aula::factory()->create(['codigo' => $aula])->id]);
}

it('tells an enrolled student so, with the weekly schedule of the active groups only', function (): void {
    $actual = grupoDeAvance($this->ingles, 2, '2026-II', 'A-204');
    Horario::factory()->for($actual)->create(['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Horario::factory()->for($actual)->create(['dia_semana' => 3, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $actual->id]);
    $retirado = grupoDeAvance(Idioma::factory()->create(['nombre' => 'Quechua']), 1, '2026-I', 'C-105');
    Horario::factory()->for($retirado)->create(['dia_semana' => 6, 'hora_inicio' => '14:00', 'hora_fin' => '18:30']);
    Matricula::factory()->retirada()->create(['persona_id' => $this->persona->id, 'grupo_id' => $retirado->id]);

    $avance = app(ResumirAvanceDelEstudiante::class)($this->persona->id);

    expect($avance->paso)->toBe(PasoDelEstudiante::Matriculado)
        ->and($avance->grupoActual?->codigo)->toBe($actual->codigo)
        ->and($avance->horario[1])->toEqual([new ClaseDeLaSemana('Inglés', '07:00', 'A-204')])
        ->and($avance->horario[3])->toEqual([new ClaseDeLaSemana('Inglés', '07:00', 'A-204')])
        ->and($avance->horario[6])->toBe([])
        ->and($avance->idiomas)->toEqual([
            new AvanceEnIdioma('Inglés', 2, true, '2026-II'),
            new AvanceEnIdioma('Quechua', 1, false, '2026-I'),
        ]);
});

it('asks to choose a group when a validated enrollment payment is still unused', function (): void {
    Pago::factory()->validado()->for($this->conceptoMatricula)->create(['persona_id' => $this->persona->id]);
    $usado = Pago::factory()->validado()->for($this->conceptoMatricula)->create(['persona_id' => $this->persona->id]);
    Matricula::factory()->retirada()->create(['persona_id' => $this->persona->id, 'pago_id' => $usado->id]);

    expect(app(ResumirAvanceDelEstudiante::class)($this->persona->id)->paso)->toBe(PasoDelEstudiante::PagoValidado);
});

it('asks to register the enrollment payment when there is nothing to use yet', function (): void {
    $usado = Pago::factory()->validado()->for($this->conceptoMatricula)->create(['persona_id' => $this->persona->id]);
    Matricula::factory()->retirada()->create(['persona_id' => $this->persona->id, 'pago_id' => $usado->id]);
    Pago::factory()->create(['persona_id' => $this->persona->id]);

    $avance = app(ResumirAvanceDelEstudiante::class)($this->persona->id);

    expect($avance->paso)->toBe(PasoDelEstudiante::SinPago)
        ->and($avance->grupoActual)->toBeNull()
        ->and(array_merge(...array_values($avance->horario)))->toBe([]);
});
