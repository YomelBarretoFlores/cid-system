<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarGrupo;
use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

function grupoDelPrograma(Programa $programa, bool $esAcelerado): DatosDeGrupo
{
    return new DatosDeGrupo(
        periodoId: test()->periodo->id,
        cicloId: test()->ciclo->id,
        codigo: 'ING-01',
        modalidad: Modalidad::Presencial,
        esAcelerado: $esAcelerado,
        cupoMaximo: 25,
        cupoMinimo: 10,
        programaId: $programa->id,
        aulaId: test()->aula->id,
    );
}

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create();
    $this->ciclo = Ciclo::factory()->create();
    $this->aula = Aula::factory()->create(['capacidad' => 30]);
});

it('ships the CILN programs with their regimen', function (): void {
    expect(Programa::query()->orderBy('codigo')->get(['codigo', 'nombre', 'regimen', 'activo'])->toArray())->toBe([
        ['codigo' => 'JA', 'nombre' => 'Jóvenes y Adultos', 'regimen' => RegimenDePrograma::Ambos->value, 'activo' => true],
        ['codigo' => 'NINOS', 'nombre' => 'Niños', 'regimen' => RegimenDePrograma::Ambos->value, 'activo' => true],
        ['codigo' => 'POSGRADO', 'nombre' => 'Posgrado', 'regimen' => RegimenDePrograma::Acelerado->value, 'activo' => true],
    ]);
});

it('refuses a group whose regular or accelerated choice the program does not allow', function (string $regimen, bool $esAcelerado, string $mensaje): void {
    $programa = Programa::factory()->create(['nombre' => 'Programa X', 'regimen' => $regimen]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoDelPrograma($programa, $esAcelerado)))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Grupo::query()->count())->toBe(0);
})->with([
    'accelerated-only program, regular group' => ['acelerado', false, 'El programa Programa X solo admite grupos acelerados.'],
    'regular-only program, accelerated group' => ['regular', true, 'El programa Programa X no admite grupos acelerados.'],
]);

it('refuses a regular group in the Posgrado program', function (): void {
    $posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();

    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoDelPrograma($posgrado, false)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El programa Posgrado solo admite grupos acelerados.');
});

it('accepts regular and accelerated groups in a program of both regimes and keeps the program', function (bool $esAcelerado): void {
    $ninos = Programa::query()->where('codigo', 'NINOS')->sole();

    $grupo = app(GuardarGrupo::class)(grupoDelPrograma($ninos, $esAcelerado));

    expect($grupo->refresh())
        ->programa_id->toBe($ninos->id)
        ->es_acelerado->toBe($esAcelerado);
})->with(['regular' => false, 'accelerated' => true]);

it('still saves a regular group of the Jóvenes y Adultos program as before', function (): void {
    $ja = Programa::query()->where('codigo', 'JA')->sole();

    $grupo = app(GuardarGrupo::class)(grupoDelPrograma($ja, false));

    expect($grupo->refresh())
        ->programa_id->toBe($ja->id)
        ->codigo->toBe('ING-01')
        ->aula_id->toBe($this->aula->id)
        ->es_acelerado->toBeFalse()
        ->cupo_maximo->toBe(25);
});

it('refuses an inactive program for a new group', function (): void {
    $programa = Programa::factory()->create(['nombre' => 'Programa cerrado', 'regimen' => 'ambos', 'activo' => false]);

    expect(fn (): Grupo => app(GuardarGrupo::class)(grupoDelPrograma($programa, false)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El programa Programa cerrado no está disponible para nuevos grupos.');
});

it('exposes the program of a group to the documents of other modules', function (): void {
    $grupo = Grupo::factory()->create(['ciclo_id' => $this->ciclo->id, 'periodo_id' => $this->periodo->id]);

    expect(app(CursoDelGrupo::class)($grupo->id)?->programa)->toBe('Jóvenes y Adultos')
        ->and(app(DetalleDeGrupos::class)([$grupo->id])[$grupo->id]->programa)->toBe('Jóvenes y Adultos');
});
