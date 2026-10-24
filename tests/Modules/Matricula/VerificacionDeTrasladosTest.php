<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Actions\RetirarMatricula;
use App\Modules\Matricula\Actions\TrasladarMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ViewMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Pest\Mixins\Expectation;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $this->ciclo = Ciclo::factory()->create(['numero' => 1]);
    $this->grupos = Grupo::factory()->count(3)->for($this->periodo)->for($this->ciclo)->create(['cupo_maximo' => 3, 'cupo_minimo' => 1]);
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoMatricula::valores()]);
});

function matricularDeVerdad(int $grupoId): Matricula
{
    $persona = Persona::factory()->create();
    $pago = Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);

    return app(MatricularEstudiante::class)($persona->id, $grupoId, $pago->id);
}

function contadoresCoherentes(): void
{
    Grupo::query()->get()->each(fn (Grupo $grupo): Expectation => expect($grupo->matriculados)->toBe(
        Matricula::query()->where(['grupo_id' => $grupo->id, 'estado' => EstadoMatricula::Activa])->count(),
    ));
}

/**
 * @return array<string, mixed>
 */
function fotoCompleta(): array
{
    return [
        'matriculas' => Matricula::query()->orderBy('id')->get(['id', 'grupo_id', 'estado', 'retirado_en', 'updated_at'])->toArray(),
        'grupos' => Grupo::query()->orderBy('id')->pluck('matriculados', 'id')->all(),
        'movimientos' => Movimiento::query()->count(),
        'auditoria' => DB::table('aud_registros')->count(),
    ];
}

it('keeps every seat counter equal to the active enrollments after transfers and withdrawals', function (): void {
    [$a, $b, $c] = $this->grupos;
    $m1 = matricularDeVerdad($a->id);
    $m2 = matricularDeVerdad($a->id);
    $m3 = matricularDeVerdad($b->id);

    app(TrasladarMatricula::class)($m1->id, $b->id, 'Cruce', $this->gestor);
    app(TrasladarMatricula::class)($m1->id, $c->id, 'Cruce', $this->gestor);
    app(RetirarMatricula::class)($m2->id, 'Viaje', $this->gestor);
    app(TrasladarMatricula::class)($m3->id, $a->id, 'Cierre', $this->gestor);
    app(RetirarMatricula::class)($m1->id, 'Viaje', $this->gestor);

    contadoresCoherentes();
    expect(Movimiento::query()->where('matricula_id', $m1->id)->orderBy('id')->pluck('tipo')->map->value->all())->toBe(['traslado', 'traslado', 'retiro']);
});

it('changes nothing, audit trail included, when the reason is blank or too long', function (string $motivo): void {
    $m = matricularDeVerdad($this->grupos[0]->id);
    $antes = fotoCompleta();

    expect(fn () => app(TrasladarMatricula::class)($m->id, $this->grupos[1]->id, $motivo, $this->gestor))->toThrow(ReglaDeNegocioIncumplida::class)
        ->and(fn () => app(RetirarMatricula::class)($m->id, $motivo, $this->gestor))->toThrow(ReglaDeNegocioIncumplida::class)
        ->and(fotoCompleta())->toBe($antes);
})->with(['blank' => [''], 'spaces' => ["  \n "], 'too long' => [str_repeat('ñ', 501)]]);

it('accepts a reason of exactly 500 multibyte characters', function (): void {
    $m = matricularDeVerdad($this->grupos[0]->id);

    app(RetirarMatricula::class)($m->id, str_repeat('ñ', 500), $this->gestor);

    expect(mb_strlen(Movimiento::query()->sole()->motivo))->toBe(500);
});

it('audits the transfer as an enrollment update and a movement insert', function (): void {
    $m = matricularDeVerdad($this->grupos[0]->id);

    app(TrasladarMatricula::class)($m->id, $this->grupos[1]->id, 'Cruce', $this->gestor);

    $mov = Movimiento::query()->sole();
    $ultima = DB::table('aud_registros')->where(['tabla' => 'mat_matriculas', 'registro_id' => $m->id])->orderByDesc('id')->first();

    expect(DB::table('aud_registros')->where(['tabla' => 'mat_matriculas', 'registro_id' => $m->id])->orderBy('id')->pluck('operacion')->all())->toBe(['INSERT', 'UPDATE'])
        ->and(json_decode($ultima->datos_nuevos, true)['grupo_id'])->toBe($this->grupos[1]->id)
        ->and(DB::table('aud_registros')->where(['tabla' => 'mat_movimientos', 'registro_id' => $mov->id])->pluck('operacion')->all())->toBe(['INSERT']);
});

it('refuses truncating the academic offer or enrollments while movements exist', function (string $sql): void {
    $m = matricularDeVerdad($this->grupos[0]->id);
    app(RetirarMatricula::class)($m->id, 'Viaje', $this->gestor);

    expect(fn () => DB::transaction(fn () => DB::statement($sql)))->toThrow(QueryException::class, 'mat_movimientos es inmutable')
        ->and(Movimiento::query()->count())->toBe(1);
})->with(['grupos' => ['TRUNCATE aca_grupos CASCADE'], 'matriculas' => ['TRUNCATE mat_matriculas CASCADE']]);

it('takes the same person advisory lock in enrollment, transfer and withdrawal', function (): void {
    $bloqueos = fn (int $personaId): int => DB::table('pg_locks')->where(['locktype' => 'advisory', 'classid' => 1, 'objid' => $personaId, 'objsubid' => 2])
        ->whereRaw('pid = pg_backend_pid()')->count();

    $m = matricularDeVerdad($this->grupos[0]->id);
    expect($bloqueos($m->persona_id))->toBe(1);

    $otra = Matricula::factory()->create(['grupo_id' => $this->grupos[0]->id]);
    Grupo::query()->whereKey($this->grupos[0]->id)->increment('matriculados');
    app(TrasladarMatricula::class)($otra->id, $this->grupos[1]->id, 'Cruce', $this->gestor);
    expect($bloqueos($otra->persona_id))->toBe(1);

    $tercera = Matricula::factory()->create(['grupo_id' => $this->grupos[2]->id]);
    Grupo::query()->whereKey($this->grupos[2]->id)->increment('matriculados');
    app(RetirarMatricula::class)($tercera->id, 'Viaje', $this->gestor);
    expect($bloqueos($tercera->persona_id))->toBe(1);
});

it('never lets a reader run transfer or withdrawal through Livewire', function (): void {
    $m = matricularDeVerdad($this->grupos[0]->id);
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]);
    $antes = fotoCompleta();
    $this->actingAs($lector);

    try {
        Livewire::test(ViewMatricula::class, ['record' => $m->getRouteKey()])
            ->callAction('trasladar', data: ['grupo_destino_id' => $this->grupos[1]->id, 'motivo' => 'Cruce'])
            ->callAction('retirar', data: ['motivo' => 'Viaje']);
    } catch (Throwable) {
    }

    try {
        Livewire::test(ListMatriculas::class)
            ->callAction(TestAction::make('trasladar')->table($m), data: ['grupo_destino_id' => $this->grupos[1]->id, 'motivo' => 'Cruce'])
            ->callAction(TestAction::make('retirar')->table($m), data: ['motivo' => 'Viaje']);
    } catch (Throwable) {
    }

    try {
        Livewire::test(ViewMatricula::class, ['record' => $m->getRouteKey()])
            ->call('mountAction', 'retirar')
            ->set('mountedActions.0.data.motivo', 'Viaje')
            ->call('callMountedAction');
    } catch (Throwable) {
    }

    expect(fotoCompleta())->toBe($antes);
});

it('forbids the list, the view and the staff ficha to a user without matricula.ver', function (): void {
    $m = matricularDeVerdad($this->grupos[0]->id);
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Gestionar->value]);

    $this->actingAs($sinPermiso);
    $this->get(MatriculaResource::getUrl('index'))->assertForbidden();
    $this->get(MatriculaResource::getUrl('view', ['record' => $m]))->assertForbidden();
    $this->get(route('panel.matriculas.ficha', $m))->assertForbidden();
});

it('forbids the staff ficha to the student who owns the enrollment and redirects a guest', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    $pago = Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);
    $m = app(MatricularEstudiante::class)($persona->id, $this->grupos[0]->id, $pago->id);

    $this->get(route('panel.matriculas.ficha', $m))->assertRedirect();
    $this->actingAs($user)->get(route('panel.matriculas.ficha', $m))->assertForbidden();
});

it('refuses the original payment for a new enrollment after a withdrawal and allows a new payment', function (): void {
    $persona = Persona::factory()->create();
    $pago = Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);
    $m = app(MatricularEstudiante::class)($persona->id, $this->grupos[0]->id, $pago->id);
    app(RetirarMatricula::class)($m->id, 'Viaje', $this->gestor);

    expect(fn () => app(MatricularEstudiante::class)($persona->id, $this->grupos[1]->id, $pago->id))->toThrow(ReglaDeNegocioIncumplida::class, 'El pago ya se usó en otra matrícula.');

    $nuevo = Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);
    app(MatricularEstudiante::class)($persona->id, $this->grupos[1]->id, $nuevo->id);
    contadoresCoherentes();
});

it('answers a retried enrollment request after a transfer', function (): void {
    $persona = Persona::factory()->create();
    $pago = Pago::factory()->validado()->for(Concepto::factory())->create(['persona_id' => $persona->id]);
    $m = app(MatricularEstudiante::class)($persona->id, $this->grupos[0]->id, $pago->id);
    app(TrasladarMatricula::class)($m->id, $this->grupos[1]->id, 'Cruce', $this->gestor);

    expect(fn () => app(MatricularEstudiante::class)($persona->id, $this->grupos[0]->id, $pago->id))->toThrow(ReglaDeNegocioIncumplida::class);
    contadoresCoherentes();
});
