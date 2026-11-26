<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\CambiarEstadoDocente;
use App\Modules\Docencia\Actions\GuardarDocente;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
});

function datosDeDocente(array $cambios = []): DatosDeDocente
{
    return new DatosDeDocente(...[
        'personaId' => test()->persona->id,
        'correoInstitucional' => 'DOCENTE.Uno@unasam.edu.pe ',
        'ruc' => '10456789012',
        'banco' => 'banco_de_la_nacion',
        'cuentaBancaria' => '04-123-456789',
        'condicionLaboralId' => CondicionLaboral::idDe('contratado'), 'gradoAcademicoId' => null,
        'idiomas' => [
            new IdiomaHabilitado(test()->ingles->id, NivelCurricular::Avanzado),
            new IdiomaHabilitado(test()->quechua->id, NivelCurricular::Basico),
        ],
        ...$cambios,
    ]);
}

it('creates the teaching permissions with the migrations', function (): void {
    expect(DB::table('permissions')->where('guard_name', 'web')->whereLike('name', 'docencia.%')->orderBy('name')->pluck('name')->all())
        ->toBe(['docencia.costos', 'docencia.gestionar', 'docencia.reportar', 'docencia.ver']);
});

it('lets readers consult the roster and managers change it, never delete it', function (): void {
    $docente = Docente::factory()->create();
    $lector = User::factory()->create()->givePermissionTo(PermisoDocencia::Ver->value);
    $gestor = User::factory()->create()->givePermissionTo(PermisoDocencia::valores());

    expect([$lector->can('viewAny', Docente::class), $lector->can('view', $docente), $lector->can('create', Docente::class), $lector->can('update', $docente), $lector->can('cambiarEstado', $docente)])
        ->toBe([true, true, false, false, false])
        ->and([$gestor->can('create', Docente::class), $gestor->can('update', $docente), $gestor->can('cambiarEstado', $docente), $gestor->can('delete', $docente)])
        ->toBe([true, true, true, false])
        ->and(User::factory()->create()->can('viewAny', Docente::class))->toBeFalse();
});

it('registers a teacher with a code, an encrypted account and the languages enabled up to a level', function (): void {
    $docente = app(GuardarDocente::class)(datosDeDocente());

    $fila = DB::table('doc_docentes')->where('id', $docente->id)->first();

    expect($docente->codigo)->toBe(sprintf('DOC-%05d', $docente->id))
        ->and($docente->refresh()->only(['persona_id', 'correo_institucional', 'ruc', 'banco', 'cuenta_bancaria']))->toBe([
            'persona_id' => $this->persona->id,
            'correo_institucional' => 'docente.uno@unasam.edu.pe',
            'ruc' => '10456789012',
            'banco' => 'banco_de_la_nacion',
            'cuenta_bancaria' => '04-123-456789',
        ])
        ->and([$docente->condicionLaboral->codigo, $docente->estado])->toBe(['contratado', EstadoDocente::Activo])
        ->and($fila->cuenta_bancaria)->not->toContain('456789')
        ->and($docente->cuenta_enmascarada)->toBe('•••• 6789')
        ->and($docente->idiomas()->orderBy('idioma_id')->get()->map(fn ($habilitado): array => [$habilitado->idioma_id, $habilitado->nivel_maximo])->all())
        ->toBe([[$this->ingles->id, NivelCurricular::Avanzado], [$this->quechua->id, NivelCurricular::Basico]]);
});

it('updates the profile and replaces the enabled languages, keeping the person', function (): void {
    $docente = app(GuardarDocente::class)(datosDeDocente());
    $otra = Persona::factory()->create();

    app(GuardarDocente::class)(datosDeDocente([
        'personaId' => $otra->id,
        'banco' => 'bcp',
        'condicionLaboralId' => CondicionLaboral::idDe('tercero'), 'gradoAcademicoId' => null,
        'idiomas' => [new IdiomaHabilitado($this->quechua->id, NivelCurricular::Intermedio)],
    ]), $docente->id);

    $docente->refresh();

    expect([$docente->persona_id, $docente->banco, $docente->condicionLaboral->codigo])->toBe([$this->persona->id, 'bcp', 'tercero'])
        ->and($docente->idiomas()->get()->map(fn ($habilitado): array => [$habilitado->idioma_id, $habilitado->nivel_maximo])->all())
        ->toBe([[$this->quechua->id, NivelCurricular::Intermedio]]);
});

it('refuses an invalid profile and leaves the roster unchanged', function (Closure $cambios, string $mensaje): void {
    app(GuardarDocente::class)(datosDeDocente());
    $antes = [DB::table('doc_docentes')->count(), DB::table('doc_docente_idiomas')->count()];

    expect(fn () => app(GuardarDocente::class)(datosDeDocente($cambios())))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and([DB::table('doc_docentes')->count(), DB::table('doc_docente_idiomas')->count()])->toBe($antes);
})->with([
    'person already a teacher' => [fn (): array => ['correoInstitucional' => 'otro@unasam.edu.pe', 'ruc' => '10999999999'], 'Esta persona ya está registrada como docente.'],
    'unknown person' => [fn (): array => ['personaId' => 999999, 'correoInstitucional' => 'otro@unasam.edu.pe', 'ruc' => '10999999999'], 'La persona elegida no existe.'],
    'repeated RUC' => [fn (): array => ['personaId' => Persona::factory()->create()->id, 'correoInstitucional' => 'otro@unasam.edu.pe'], 'Ya existe un docente con el RUC 10456789012.'],
    'repeated email' => [fn (): array => ['personaId' => Persona::factory()->create()->id, 'ruc' => '10999999999'], 'Ya existe un docente con el correo institucional docente.uno@unasam.edu.pe.'],
    'short RUC' => [fn (): array => ['ruc' => '1045678901'], 'El RUC debe tener 11 dígitos.'],
    'letters in the account' => [fn (): array => ['cuentaBancaria' => '04-ABC-1234'], 'El número de cuenta solo admite dígitos y guiones, entre 6 y 30 caracteres.'],
    'invalid email' => [fn (): array => ['correoInstitucional' => 'docente@'], 'Escribe un correo institucional válido.'],
    'no bank' => [fn (): array => ['banco' => '  '], 'Indica el banco de la cuenta.'],
    'no languages' => [fn (): array => ['idiomas' => []], 'Habilita al menos un idioma para dictar.'],
    'repeated language' => [fn (): array => ['idiomas' => [new IdiomaHabilitado(test()->ingles->id, NivelCurricular::Basico), new IdiomaHabilitado(test()->ingles->id, NivelCurricular::Avanzado)]], 'Cada idioma se habilita una sola vez.'],
    'unknown language' => [fn (): array => ['idiomas' => [new IdiomaHabilitado(999999, NivelCurricular::Basico)]], 'El idioma elegido no existe.'],
]);

it('refuses an update that takes the RUC or email of another teacher and keeps the profile', function (array $cambios, string $mensaje): void {
    app(GuardarDocente::class)(datosDeDocente());
    $segundo = app(GuardarDocente::class)(datosDeDocente([
        'personaId' => Persona::factory()->create()->id, 'correoInstitucional' => 'dos@unasam.edu.pe', 'ruc' => '10222222222',
        'idiomas' => [new IdiomaHabilitado(test()->quechua->id, NivelCurricular::Basico)],
    ]));

    expect(fn () => app(GuardarDocente::class)(datosDeDocente([...$cambios, 'banco' => 'bcp']), $segundo->id))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and($segundo->refresh()->only(['correo_institucional', 'ruc', 'banco']))->toBe(['correo_institucional' => 'dos@unasam.edu.pe', 'ruc' => '10222222222', 'banco' => 'banco_de_la_nacion'])
        ->and($segundo->idiomas()->pluck('idioma_id')->all())->toBe([test()->quechua->id]);
})->with([
    'RUC' => [['correoInstitucional' => 'dos@unasam.edu.pe'], 'Ya existe un docente con el RUC 10456789012.'],
    'email' => [['ruc' => '10222222222'], 'Ya existe un docente con el correo institucional docente.uno@unasam.edu.pe.'],
]);

it('changes the status of a teacher and refuses the status it already has', function (): void {
    $docente = app(GuardarDocente::class)(datosDeDocente());

    app(CambiarEstadoDocente::class)($docente->id, EstadoDocente::DeLicencia);

    expect($docente->refresh()->estado)->toBe(EstadoDocente::DeLicencia)
        ->and(fn () => app(CambiarEstadoDocente::class)($docente->id, EstadoDocente::DeLicencia))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El docente ya está De licencia.');
});

it('audits the roster and the enabled languages', function (): void {
    $docente = app(GuardarDocente::class)(datosDeDocente());
    app(CambiarEstadoDocente::class)($docente->id, EstadoDocente::Inactivo);

    $operaciones = fn (string $tabla): array => DB::table('aud_registros')->where('tabla', $tabla)->orderBy('id')->pluck('operacion')->all();

    expect($operaciones('doc_docentes'))->toBe(['INSERT', 'UPDATE'])
        ->and($operaciones('doc_docente_idiomas'))->toBe(['INSERT', 'INSERT']);
});
