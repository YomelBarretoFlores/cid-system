<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Contracts\FinDeLaUltimaMatricula;
use App\Modules\Matricula\Contracts\HabilitacionPorTramite;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->usuario = User::factory()->create();
    $this->idioma = ActasFinales::planDeEstudios('Inglés');
    $this->ciclo = fn (int $numero): int => (int) Ciclo::query()->where(['idioma_id' => $this->idioma->id, 'numero' => $numero])->value('id');
});

it('moves the enabled cycle forward for a proficiency exam but never lowers it', function (): void {
    $habilitar = app(HabilitacionPorTramite::class);

    expect($habilitar($this->persona->id, ($this->ciclo)(5), OrigenHabilitacion::Suficiencia, $this->usuario->id, true))->toBeTrue()
        ->and($habilitar($this->persona->id, ($this->ciclo)(3), OrigenHabilitacion::Suficiencia, $this->usuario->id, true))->toBeFalse()
        ->and(Habilitacion::query()->where('persona_id', $this->persona->id)->sole())
        ->ciclo_id->toBe(($this->ciclo)(5))->origen->toBe(OrigenHabilitacion::Suficiencia)->actualizado_por->toBe($this->usuario->id)->motivo->toBeNull();
});

it('sets exactly the reclassification cycle, lower ones included', function (): void {
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->idioma->id, 'ciclo_id' => ($this->ciclo)(9)]);

    expect(app(HabilitacionPorTramite::class)($this->persona->id, ($this->ciclo)(2), OrigenHabilitacion::Reclasificacion, $this->usuario->id, false))->toBeTrue()
        ->and(Habilitacion::query()->where('persona_id', $this->persona->id)->sole())->ciclo_id->toBe(($this->ciclo)(2))->origen->toBe(OrigenHabilitacion::Reclasificacion);
});

it('tells when the latest enrollment in a language ended, withdrawn ones included and other languages ignored', function (): void {
    $enPeriodo = function (string $idioma, string $fin, EstadoMatricula $estado = EstadoMatricula::Activa): void {
        $ciclo = Ciclo::query()->where(['idioma_id' => ActasFinales::planDeEstudios($idioma)->id, 'numero' => 1])->sole();
        $periodo = Periodo::factory()->create(['fecha_inicio' => substr($fin, 0, 8).'01', 'fecha_fin' => $fin, 'matricula_inicio' => '2026-12-01', 'matricula_fin' => substr($fin, 0, 8).'03']);
        ($estado === EstadoMatricula::Retirada ? Matricula::factory()->retirada() : Matricula::factory())->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id])->id]);
    };

    expect(app(FinDeLaUltimaMatricula::class)($this->persona->id, 'Inglés'))->toBeNull();

    $enPeriodo('Inglés', '2027-01-31');
    $enPeriodo('Inglés', '2027-02-28', EstadoMatricula::Retirada);
    $enPeriodo('Francés', '2027-05-31');

    expect(app(FinDeLaUltimaMatricula::class)($this->persona->id, 'Inglés')?->toDateString())->toBe('2027-02-28');
});
