<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeMatriculaDelGrupo;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->emisor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value]);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-1-M']);
    $this->ana = Matricula::factory()->activa()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Alva', 'nombres' => 'Ana'])->id]);
    $this->beto = Matricula::factory()->activa()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Bravo', 'nombres' => 'Beto'])->id]);
    $this->ciro = Matricula::factory()->activa()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Cano', 'nombres' => 'Ciro'])->id]);
});

/**
 * @return list<int|null>
 */
function matriculasConConstancia(): array
{
    return Documento::query()->where('tipo', TipoDocumento::ConstanciaMatricula)->whereNull('anulado_en')->orderBy('matricula_id')->pluck('matricula_id')->all();
}

it('issues one enrollment record per active enrollment of the group and skips the ones that already have a valid one', function (): void {
    app(EmitirConstanciaDeMatricula::class)($this->beto->id, $this->emisor);
    $anulada = app(EmitirConstanciaDeMatricula::class)($this->ciro->id, $this->emisor);
    app(AnularDocumento::class)($anulada->id, 'Se emitió con un error.', $this->emisor);

    $primera = app(EmitirConstanciasDeMatriculaDelGrupo::class)($this->grupo->id, $this->emisor);
    $segunda = app(EmitirConstanciasDeMatriculaDelGrupo::class)($this->grupo->id, $this->emisor);

    expect(array_map(fn (Documento $documento): ?int => $documento->matricula_id, $primera->emitidos))->toBe([$this->ana->id, $this->ciro->id])
        ->and($primera->emitidos[0])->tipo->toBe(TipoDocumento::ConstanciaMatricula)->persona_id->toBe($this->ana->persona_id)
        ->and($primera->emitidos[0]->detalle)->toMatchArray(['grupo' => 'ING-1-M', 'codigo_matricula' => $this->ana->ulid])
        ->and($primera->omitidos)->toBe(1)
        ->and($primera->fallidos)->toBe([])
        ->and($segunda->emitidos)->toBe([])
        ->and($segunda->omitidos)->toBe(3)
        ->and(matriculasConConstancia())->toBe([$this->ana->id, $this->beto->id, $this->ciro->id]);
});

it('ignores withdrawn enrollments of the group like the individual issue', function (): void {
    $retirada = Matricula::factory()->retirada()->create(['grupo_id' => $this->grupo->id]);

    $resumen = app(EmitirConstanciasDeMatriculaDelGrupo::class)($this->grupo->id, $this->emisor);

    expect($resumen->emitidos)->toHaveCount(3)
        ->and(Documento::query()->where('matricula_id', $retirada->id)->exists())->toBeFalse();
});

it('goes on with the rest of the group when one enrollment record is refused and reports it', function (): void {
    Documento::creating(function (Documento $documento): void {
        if ($documento->matricula_id === $this->beto->id) {
            throw new ReglaDeNegocioIncumplida('No se pudo leer la ficha de la persona.');
        }
    });

    $resumen = app(EmitirConstanciasDeMatriculaDelGrupo::class)($this->grupo->id, $this->emisor);

    expect($resumen->fallidos)->toBe([Persona::query()->findOrFail($this->beto->persona_id)->nombre_completo.': No se pudo leer la ficha de la persona.'])
        ->and($resumen->emitidos)->toHaveCount(2)
        ->and(matriculasConConstancia())->toBe([$this->ana->id, $this->ciro->id]);
});

it('requires the same permission as the individual issue', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    expect(fn () => app(EmitirConstanciasDeMatriculaDelGrupo::class)($this->grupo->id, $sinPermiso))->toThrow(AuthorizationException::class)
        ->and(Documento::query()->count())->toBe(0);

    $this->actingAs($sinPermiso)->get(DocumentoResource::getUrl('index'))->assertForbidden();
});

it('issues the enrollment records of a group from the documents panel and reports issued and omitted', function (): void {
    app(EmitirConstanciaDeMatricula::class)($this->beto->id, $this->emisor);

    Livewire::actingAs($this->emisor)->test(ListDocumentos::class)
        ->callAction('emitirConstanciasDeMatriculaDelGrupo', ['grupo_id' => $this->grupo->id])
        ->assertHasNoActionErrors()
        ->assertNotified('2 constancias emitidas');

    expect(matriculasConConstancia())->toBe([$this->ana->id, $this->beto->id, $this->ciro->id]);
});
