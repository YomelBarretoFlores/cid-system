<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\DesignarDelegado;
use App\Modules\Docencia\Models\Delegado;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($this->grupo, $this->docente);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id]);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id]);
});

function designarDelegado(int $matriculaId, ?Docente $docente = null): Delegado
{
    return app(DesignarDelegado::class)(test()->grupo->id, ($docente ?? test()->docente)->id, $matriculaId, test()->usuario);
}

it('designates an active student of the group as its delegate and records who did it', function (): void {
    $delegado = designarDelegado($this->ana->id);

    expect($delegado->only(['grupo_id', 'matricula_id', 'designado_por']))->toBe(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'designado_por' => $this->usuario->id])
        ->and(app(DelegadoDelGrupo::class)($this->grupo->id)?->nombreCompleto)->toBe('Quispe Rojas, Ana')
        ->and(DB::table('aud_registros')->where('tabla', 'doc_delegados')->count())->toBe(1);
});

it('changes the delegate of the group, keeping one per group', function (): void {
    designarDelegado($this->ana->id);

    designarDelegado($this->luis->id);

    expect(Delegado::query()->where('grupo_id', $this->grupo->id)->pluck('matricula_id')->all())->toBe([$this->luis->id])
        ->and(app(DelegadoDelGrupo::class)($this->grupo->id)?->matriculaId)->toBe($this->luis->id);
});

it('refuses a designation that breaks a rule and keeps the current delegate', function (Closure $preparar, string $mensaje): void {
    designarDelegado($this->luis->id);
    [$matriculaId, $docente] = $preparar->call($this) + [$this->ana->id, null];

    expect(fn (): Delegado => designarDelegado($matriculaId, $docente))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Delegado::query()->pluck('matricula_id')->all())->toBe([$this->luis->id]);
})->with([
    'another teacher' => [fn (): array => [1 => Docente::factory()->create()], 'No tienes a cargo el grupo ING-01-A.'],
    'cancelled group' => [function (): array {
        $this->grupo->update(['estado' => EstadoGrupo::Cancelado]);

        return [];
    }, 'El grupo ING-01-A está Cancelado; el delegado solo se designa en grupos abiertos o cerrados.'],
    'student of another group' => [fn (): array => [0 => Matricula::factory()->create()->id], 'El estudiante elegido no tiene matrícula activa en el grupo ING-01-A.'],
    'withdrawn student' => [function (): array {
        $this->ana->update(['estado' => 'retirada', 'retirado_en' => now()]);

        return [];
    }, 'El estudiante elegido no tiene matrícula activa en el grupo ING-01-A.'],
]);

it('stops treating a student as delegate once the enrollment is no longer active', function (): void {
    designarDelegado($this->ana->id);

    $this->ana->update(['estado' => 'retirada', 'retirado_en' => now()]);

    expect(app(DelegadoDelGrupo::class)($this->grupo->id))->toBeNull();
});
