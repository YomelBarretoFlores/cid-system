<?php

declare(strict_types=1);

use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

it('creates the placement test permissions with the migrations', function (): void {
    expect(DB::table('permissions')->where('guard_name', 'web')->whereLike('name', 'clasificacion.%')->orderBy('name')->pluck('name')->all())
        ->toBe(['clasificacion.evaluar', 'clasificacion.ver']);
});

it('lets readers consult requests and evaluators record results', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoClasificacion::Ver->value);
    $evaluador = User::factory()->create()->givePermissionTo(PermisoClasificacion::valores());

    expect([$lector->can('viewAny', SolicitudClasificacion::class), $lector->can('view', $solicitud), $lector->can('evaluar', $solicitud)])
        ->toBe([true, true, false])
        ->and($evaluador->can('evaluar', $solicitud))->toBeTrue()
        ->and([$evaluador->can('create', SolicitudClasificacion::class), $evaluador->can('update', $solicitud), $evaluador->can('delete', $solicitud)])->toBe([false, false, false])
        ->and(User::factory()->create()->can('viewAny', SolicitudClasificacion::class))->toBeFalse();
});

it('audits placement requests and enabled cycles', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create();
    $habilitacion = Habilitacion::factory()->create();

    $operaciones = fn (string $tabla, int $id): array => DB::table('aud_registros')->where(['tabla' => $tabla, 'registro_id' => $id])->orderBy('id')->pluck('operacion')->all();

    expect($operaciones('mat_solicitudes_clasificacion', $solicitud->id))->toBe(['INSERT'])
        ->and($operaciones('mat_habilitaciones', $habilitacion->id))->toBe(['INSERT']);
});
