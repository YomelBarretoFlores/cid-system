<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\CambiarEstadoGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;
use Tests\Support\OfertaAcademica;

beforeEach(function (): void {
    $this->periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'estado' => EstadoPeriodo::Planificado]);
    $this->grupo = Grupo::factory()->for($this->periodo)->create(['codigo' => 'ING-01-A', 'estado' => EstadoGrupo::Programado, 'cupo_minimo' => 10]);
});

it('refuses to open a group that is missing its schedule, its teacher or a period that allows enrollment, listing what is missing', function (Closure $preparar, string $mensaje): void {
    $preparar($this->grupo, $this->periodo);

    expect(fn (): Grupo => app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Abierto))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Programado);
})->with([
    'nothing ready' => [fn (Grupo $grupo, Periodo $periodo) => $periodo->update(['estado' => EstadoPeriodo::EnCurso]),
        'No se puede abrir el grupo ING-01-A: no tiene horario; no tiene docente asignado; su periodo Marzo 2027 está En curso y solo se abren grupos de periodos Planificados o con matrícula abierta.'],
    'no schedule' => [OfertaAcademica::asignarDocente(...),
        'No se puede abrir el grupo ING-01-A: no tiene horario.'],
    'no teacher' => [fn (Grupo $grupo) => Horario::factory()->for($grupo)->create(),
        'No se puede abrir el grupo ING-01-A: no tiene docente asignado.'],
    'closed period' => [function (Grupo $grupo, Periodo $periodo): void {
        OfertaAcademica::prepararParaAbrir($grupo);
        $periodo->update(['estado' => EstadoPeriodo::Cerrado]);
    }, 'No se puede abrir el grupo ING-01-A: su periodo Marzo 2027 está Cerrado y solo se abren grupos de periodos Planificados o con matrícula abierta.'],
]);

it('opens a group with schedule and teacher in a planned period or one with enrollment open', function (EstadoPeriodo $estado): void {
    $this->periodo->update(['estado' => $estado]);
    OfertaAcademica::prepararParaAbrir($this->grupo);

    expect(app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Abierto)->estado)->toBe(EstadoGrupo::Abierto)
        ->and($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Abierto);
})->with([
    'planned' => [EstadoPeriodo::Planificado],
    'enrollment open' => [EstadoPeriodo::MatriculaAbierta],
]);

it('refuses to close an open group below its minimum quota unless that is confirmed', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Abierto]);
    OfertaAcademica::matricular($this->grupo, 4);

    expect(fn (): Grupo => app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Cerrado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El grupo ING-01-A tiene 4 matriculados y el mínimo de apertura es 10. Para iniciarlo de todas formas hay que confirmarlo.')
        ->and($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Abierto);
});

it('closes an open group below its minimum quota when confirmed and logs who confirmed it', function (): void {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);
    $this->grupo->update(['estado' => EstadoGrupo::Abierto]);
    OfertaAcademica::matricular($this->grupo, 4);
    Log::spy();

    app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Cerrado, bajoElMinimo: true);

    expect($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Cerrado);
    Log::shouldHaveReceived('info')->once()->with('Grupo iniciado por debajo del cupo mínimo de apertura.', [
        'grupo_id' => $this->grupo->id, 'matriculados' => 4, 'cupo_minimo' => 10, 'usuario_id' => $usuario->id,
    ]);
});

it('closes an open group that reached its minimum quota without asking', function (): void {
    $this->grupo->update(['estado' => EstadoGrupo::Abierto]);
    OfertaAcademica::matricular($this->grupo, 10);

    expect(app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Cerrado)->estado)->toBe(EstadoGrupo::Cerrado);
});

it('refuses to finish a group while its period still has enrollment open', function (): void {
    $this->periodo->update(['estado' => EstadoPeriodo::MatriculaAbierta]);
    $this->grupo->update(['estado' => EstadoGrupo::Cerrado]);

    expect(fn (): Grupo => app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Finalizado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No se puede finalizar el grupo ING-01-A mientras la matrícula del periodo Marzo 2027 está abierta.')
        ->and($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Cerrado);
});

it('finishes a closed group once its period is in progress', function (): void {
    $this->periodo->update(['estado' => EstadoPeriodo::EnCurso]);
    $this->grupo->update(['estado' => EstadoGrupo::Cerrado]);

    expect(app(CambiarEstadoGrupo::class)($this->grupo->id, EstadoGrupo::Finalizado)->estado)->toBe(EstadoGrupo::Finalizado);
});
