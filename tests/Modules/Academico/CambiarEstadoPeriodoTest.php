<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\CambiarEstadoPeriodo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

it('moves a period forward one state at a time', function (EstadoPeriodo $origen, EstadoPeriodo $destino, bool $permitida): void {
    expect($origen->puedeTransitarA($destino))->toBe($permitida);
})->with([
    'planned to enrollment open' => [EstadoPeriodo::Planificado, EstadoPeriodo::MatriculaAbierta, true],
    'enrollment open to in progress' => [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::EnCurso, true],
    'in progress to closed' => [EstadoPeriodo::EnCurso, EstadoPeriodo::Cerrado, true],
    'planned to in progress' => [EstadoPeriodo::Planificado, EstadoPeriodo::EnCurso, false],
    'planned to closed' => [EstadoPeriodo::Planificado, EstadoPeriodo::Cerrado, false],
    'enrollment open to planned' => [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::Planificado, false],
    'enrollment open to closed' => [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::Cerrado, false],
    'in progress to planned' => [EstadoPeriodo::EnCurso, EstadoPeriodo::Planificado, false],
    'in progress to enrollment open' => [EstadoPeriodo::EnCurso, EstadoPeriodo::MatriculaAbierta, false],
    'closed to planned' => [EstadoPeriodo::Cerrado, EstadoPeriodo::Planificado, false],
    'closed to enrollment open' => [EstadoPeriodo::Cerrado, EstadoPeriodo::MatriculaAbierta, false],
    'closed to in progress' => [EstadoPeriodo::Cerrado, EstadoPeriodo::EnCurso, false],
]);

it('names the next state of each period state', function (EstadoPeriodo $estado, ?EstadoPeriodo $siguiente): void {
    expect($estado->siguiente())->toBe($siguiente);
})->with([
    'planned' => [EstadoPeriodo::Planificado, EstadoPeriodo::MatriculaAbierta],
    'enrollment open' => [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::EnCurso],
    'in progress' => [EstadoPeriodo::EnCurso, EstadoPeriodo::Cerrado],
    'closed' => [EstadoPeriodo::Cerrado, null],
]);

it('changes the state of a period to the next one', function (EstadoPeriodo $origen, EstadoPeriodo $destino): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-01 08:00', 'America/Lima'));
    $periodo = Periodo::factory()->create(['estado' => $origen]);

    $resultado = app(CambiarEstadoPeriodo::class)($periodo->id, $destino);

    expect($periodo->refresh()->estado)->toBe($destino)
        ->and($resultado->is($periodo))->toBeTrue()
        ->and($resultado->estado)->toBe($destino);
})->with([
    'open enrollment' => [EstadoPeriodo::Planificado, EstadoPeriodo::MatriculaAbierta],
    'start classes' => [EstadoPeriodo::MatriculaAbierta, EstadoPeriodo::EnCurso],
    'close' => [EstadoPeriodo::EnCurso, EstadoPeriodo::Cerrado],
]);

it('treats moving a period to its current state as a harmless retry', function (): void {
    $this->travelTo(now()->startOfSecond());
    $periodo = Periodo::factory()->create(['estado' => EstadoPeriodo::MatriculaAbierta]);
    $actualizadoEn = $periodo->updated_at;
    $this->travel(5)->minutes();

    $resultado = app(CambiarEstadoPeriodo::class)($periodo->id, EstadoPeriodo::MatriculaAbierta);

    expect($periodo->refresh())
        ->estado->toBe(EstadoPeriodo::MatriculaAbierta)
        ->updated_at->toEqual($actualizadoEn)
        ->and($resultado->is($periodo))->toBeTrue();
});

it('refuses to skip or go back a state, explaining why', function (EstadoPeriodo $origen, EstadoPeriodo $destino, string $mensaje): void {
    $periodo = Periodo::factory()->create(['estado' => $origen]);

    expect(fn () => app(CambiarEstadoPeriodo::class)($periodo->id, $destino))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect($periodo->refresh()->estado)->toBe($origen);
})->with([
    'skip enrollment' => [EstadoPeriodo::Planificado, EstadoPeriodo::EnCurso, 'Un periodo Planificado no puede pasar a En curso.'],
    'reopen enrollment' => [EstadoPeriodo::EnCurso, EstadoPeriodo::MatriculaAbierta, 'Un periodo En curso no puede pasar a Matrícula abierta.'],
    'reopen a closed period' => [EstadoPeriodo::Cerrado, EstadoPeriodo::Planificado, 'Un periodo Cerrado no puede pasar a Planificado.'],
]);

it('refuses to close a period while any of its groups is still open, naming them', function (): void {
    $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'estado' => EstadoPeriodo::EnCurso]);
    Grupo::factory()->for($periodo)->create(['codigo' => 'ING-01-B', 'estado' => EstadoGrupo::Abierto]);
    Grupo::factory()->for($periodo)->create(['codigo' => 'ING-01-A', 'estado' => EstadoGrupo::Abierto]);
    $cerrado = Grupo::factory()->for($periodo)->create(['estado' => EstadoGrupo::Cerrado]);

    expect(fn () => app(CambiarEstadoPeriodo::class)($periodo->id, EstadoPeriodo::Cerrado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No se puede cerrar el periodo Marzo 2027 mientras tenga grupos abiertos: ING-01-A, ING-01-B. Ciérralos o cancélalos antes.')
        ->and($periodo->refresh()->estado)->toBe(EstadoPeriodo::EnCurso)
        ->and($cerrado->refresh()->estado)->toBe(EstadoGrupo::Cerrado);
});

it('closing a period finishes its closed groups and cancels its scheduled groups', function (): void {
    $periodo = Periodo::factory()->create(['estado' => EstadoPeriodo::EnCurso]);
    $cerrado = Grupo::factory()->for($periodo)->create(['estado' => EstadoGrupo::Cerrado]);
    $programado = Grupo::factory()->for($periodo)->create(['estado' => EstadoGrupo::Programado]);
    $cancelado = Grupo::factory()->for($periodo)->create(['estado' => EstadoGrupo::Cancelado]);
    $ajeno = Grupo::factory()->create(['estado' => EstadoGrupo::Cerrado]);

    app(CambiarEstadoPeriodo::class)($periodo->id, EstadoPeriodo::Cerrado);

    expect($periodo->refresh()->estado)->toBe(EstadoPeriodo::Cerrado)
        ->and($cerrado->refresh()->estado)->toBe(EstadoGrupo::Finalizado)
        ->and($programado->refresh()->estado)->toBe(EstadoGrupo::Cancelado)
        ->and($cancelado->refresh()->estado)->toBe(EstadoGrupo::Cancelado)
        ->and($ajeno->refresh()->estado)->toBe(EstadoGrupo::Cerrado);
});

it('refuses to start classes before the period start date unless that is confirmed', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-28 23:30', 'America/Lima'));
    $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'estado' => EstadoPeriodo::MatriculaAbierta]);

    expect(fn () => app(CambiarEstadoPeriodo::class)($periodo->id, EstadoPeriodo::EnCurso))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Las clases del periodo Marzo 2027 empiezan el 01/03/2027. Para iniciarlas antes hay que confirmarlo.')
        ->and($periodo->refresh()->estado)->toBe(EstadoPeriodo::MatriculaAbierta);

    app(CambiarEstadoPeriodo::class)($periodo->id, EstadoPeriodo::EnCurso, antesDelInicio: true);

    expect($periodo->refresh()->estado)->toBe(EstadoPeriodo::EnCurso);
});
