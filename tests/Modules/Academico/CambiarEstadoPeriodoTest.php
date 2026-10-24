<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\CambiarEstadoPeriodo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

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
