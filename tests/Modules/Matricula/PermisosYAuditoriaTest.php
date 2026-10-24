<?php

declare(strict_types=1);

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('creates the enrollment permissions with the migrations', function (): void {
    expect(DB::table('permissions')->where('guard_name', 'web')->whereLike('name', 'matricula.%')->orderBy('name')->pluck('name')->all())
        ->toBe(['matricula.gestionar', 'matricula.ver']);
});

it('lets readers consult enrollments and managers transfer and withdraw them', function (): void {
    $matricula = Matricula::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoMatricula::Ver->value);
    $gestor = User::factory()->create()->givePermissionTo(PermisoMatricula::valores());
    $ajeno = User::factory()->create();

    expect([$lector->can('viewAny', Matricula::class), $lector->can('view', $matricula), $lector->can('trasladar', $matricula), $lector->can('retirar', $matricula)])
        ->toBe([true, true, false, false])
        ->and([$gestor->can('trasladar', $matricula), $gestor->can('retirar', $matricula)])->toBe([true, true])
        ->and([$gestor->can('create', Matricula::class), $gestor->can('update', $matricula), $gestor->can('delete', $matricula)])->toBe([false, false, false])
        ->and($ajeno->can('viewAny', Matricula::class))->toBeFalse();
});

it('never edits or deletes a movement', function (Closure $cambio): void {
    $movimiento = Movimiento::factory()->create();

    expect(fn () => $cambio($movimiento))->toThrow(QueryException::class, 'mat_movimientos es inmutable');
})->with([
    'update' => [fn (Movimiento $movimiento) => DB::table('mat_movimientos')->where('id', $movimiento->id)->update(['motivo' => 'Otro motivo'])],
    'delete' => [fn (Movimiento $movimiento) => DB::table('mat_movimientos')->where('id', $movimiento->id)->delete()],
]);

it('audits enrollments and movements', function (): void {
    $matricula = Matricula::factory()->create();
    $matricula->update(['estado' => 'retirada', 'retirado_en' => now()]);
    Movimiento::factory()->create();

    expect(DB::table('aud_registros')->where('tabla', 'mat_matriculas')->where('registro_id', (string) $matricula->id)->pluck('operacion')->all())->toBe(['INSERT', 'UPDATE'])
        ->and(DB::table('aud_registros')->where('tabla', 'mat_movimientos')->where('operacion', 'INSERT')->exists())->toBeTrue();
});
