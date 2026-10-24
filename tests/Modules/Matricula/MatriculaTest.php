<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('creates active enrollments with a public ULID', function (): void {
    $matricula = Matricula::factory()->create(['condicion' => CondicionMatricula::Repitente]);

    expect($matricula->refresh()->estado)->toBe(EstadoMatricula::Activa)
        ->and($matricula->condicion)->toBe(CondicionMatricula::Repitente)
        ->and(Str::isUlid($matricula->ulid))->toBeTrue();
});

it('prevents enrolling twice in the same group while active', function (): void {
    $matricula = Matricula::factory()->create();

    expect(fn () => Matricula::factory()->create([
        'persona_id' => $matricula->persona_id,
        'grupo_id' => $matricula->grupo_id,
    ]))->toThrow(QueryException::class, 'mat_matriculas_activa_unique');
});

it('allows enrolling again in a group after withdrawing', function (): void {
    $retirada = Matricula::factory()->retirada()->create();

    $nueva = Matricula::factory()->create(['persona_id' => $retirada->persona_id, 'grupo_id' => $retirada->grupo_id]);

    expect($nueva->exists)->toBeTrue();
});

it('requires a withdrawal date exactly when the enrollment is withdrawn', function (array $attributes): void {
    expect(fn () => Matricula::factory()->create($attributes))
        ->toThrow(QueryException::class, 'mat_matriculas_retiro_check');
})->with([
    'withdrawn without date' => [['estado' => EstadoMatricula::Retirada, 'retirado_en' => null]],
    'active with withdrawal date' => [['estado' => EstadoMatricula::Activa, 'retirado_en' => '2027-03-10 10:00:00']],
]);

it('refuses to delete a group that has enrollments', function (): void {
    $matricula = Matricula::factory()->create();

    expect(fn () => Grupo::query()->whereKey($matricula->grupo_id)->delete())
        ->toThrow(QueryException::class, 'mat_matriculas_grupo_id_foreign');
});

it('records transfers and withdrawals with their authorization', function (): void {
    $matricula = Matricula::factory()->create();
    $retiro = Movimiento::factory()->for($matricula)->create(['efectuado_en' => '2027-03-20 09:00:00']);
    $traslado = Movimiento::factory()->for($matricula)->traslado()->create(['efectuado_en' => '2027-03-10 09:00:00']);

    expect($matricula->movimientos->pluck('id')->all())->toBe([$traslado->id, $retiro->id])
        ->and($traslado->tipo)->toBe(TipoMovimiento::Traslado)
        ->and($retiro->grupo_origen_id)->toBe($matricula->grupo_id)
        ->and($retiro->load('autorizador')->autorizador)->not->toBeNull();
});

it('rejects inconsistent transfers and withdrawals', function (Closure $movimiento, string $constraint): void {
    expect($movimiento)->toThrow(QueryException::class, $constraint);
})->with([
    'transfer without destination' => [fn () => Movimiento::factory()->create(['tipo' => TipoMovimiento::Traslado]), 'mat_movimientos_destino_check'],
    'transfer to the same group' => [fn () => Movimiento::factory()->traslado()->create([
        'grupo_destino_id' => fn (array $attributes): int => $attributes['grupo_origen_id'],
    ]), 'mat_movimientos_destino_check'],
    'withdrawal with destination' => [fn () => Movimiento::factory()->create(['grupo_destino_id' => Grupo::factory()]), 'mat_movimientos_destino_check'],
    'blank reason' => [fn () => Movimiento::factory()->create(['motivo' => '   ']), 'mat_movimientos_motivo_check'],
]);
