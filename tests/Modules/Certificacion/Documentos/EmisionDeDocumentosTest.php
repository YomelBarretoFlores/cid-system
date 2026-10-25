<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirCertificadosDelPeriodo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeNotasDelActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
});

function basicoCompleto(Persona $persona, string $idioma = 'Inglés'): void
{
    ActasFinales::historial($persona, $idioma, [[1, '14.00', 'aprobado'], [2, '10.00', 'desaprobado'], [2, '15.50', 'aprobado'], [3, '16.25', 'aprobado'], [4, '13.00', 'aprobado']]);
}

it('issues a level certificate with a unique code, the four passed cycles, the sum of their hours and the average of their grades', function (): void {
    basicoCompleto($this->ana);

    $documento = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    expect($documento->refresh())
        ->tipo->toBe(TipoDocumento::CertificadoNivel)
        ->estudiante_nombre->toBe('Alva Prueba, Ana')
        ->estudiante_documento->toBe('DNI 70000001')
        ->idioma->toBe('Inglés')
        ->nivel->toBe(NivelCurricular::Basico)
        ->horas_lectivas->toBe(240)
        ->nota_final->toBe('14.69')
        ->emitido_por->toBe($this->emisor->id)
        ->and($documento->codigo)->toMatch('/^CILN-[0-9A-HJKMNP-TV-Z]{10}$/')
        ->and(array_column($documento->detalle['ciclos'], 'promedio', 'ciclo_numero'))->toBe([1 => '14.00', 2 => '15.50', 3 => '16.25', 4 => '13.00'])
        ->and(DB::table('aud_registros')->where('tabla', 'cer_documentos')->count())->toBe(1);
});

it('refuses a certificate when a cycle of the level is not passed in a closed acta, or one is already valid', function (Closure $preparar, string $mensaje): void {
    $preparar->call($this);

    expect(fn () => app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'a failed cycle' => [fn () => ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '10.00', 'desaprobado'], [3, '16.00', 'aprobado'], [4, '13.00', 'aprobado']]), 'La persona aún no aprueba los cuatro ciclos del nivel Básico de Inglés en actas cerradas.'],
    'a cycle passed in another language' => [function (): void {
        ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '16.00', 'aprobado']]);
        ActasFinales::historial($this->ana, 'Francés', [[4, '13.00', 'aprobado']]);
    }, 'La persona aún no aprueba los cuatro ciclos del nivel Básico de Inglés en actas cerradas.'],
    'an acta under rectification' => [function (): void {
        ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '16.00', 'aprobado']]);
        ActasFinales::historial($this->ana, 'Inglés', [[4, '13.00', 'aprobado']], 'abierta');
    }, 'La persona aún no aprueba los cuatro ciclos del nivel Básico de Inglés en actas cerradas.'],
    'a valid certificate already issued' => [function (): void {
        basicoCompleto($this->ana);
        $this->previo = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    }, 'La persona ya tiene un certificado vigente del nivel Básico de Inglés: CILN-'],
]);

it('issues a new certificate once the previous one is voided', function (): void {
    basicoCompleto($this->ana);
    $previo = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    app(AnularDocumento::class)($previo->id, 'Se emitió con un nombre mal escrito.', $this->emisor);

    $nuevo = app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    expect($nuevo->codigo)->not->toBe($previo->codigo)
        ->and(Documento::query()->whereNull('anulado_en')->pluck('id')->all())->toBe([$nuevo->id]);
});

it('issues a grade record of a language with every closed acta, NSP included', function (): void {
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, null, 'nsp']]);
    ActasFinales::historial($this->ana, 'Inglés', [[3, '18.00', 'aprobado']], 'abierta');
    ActasFinales::historial($this->ana, 'Francés', [[1, '12.00', 'aprobado']]);

    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);

    expect($documento->tipo)->toBe(TipoDocumento::ConstanciaNotas)
        ->and($documento->nivel)->toBeNull()
        ->and(array_map(fn (array $nota): array => [$nota['ciclo_numero'], $nota['promedio'], $nota['estado_final']], $documento->detalle['notas']))->toBe([[1, '14.00', 'aprobado'], [2, null, 'nsp']])
        ->and(fn () => app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Italiano', $this->emisor))->toThrow(ReglaDeNegocioIncumplida::class, 'La persona no tiene notas finales de Italiano en actas cerradas.');
});

it('issues an enrollment record only for an active enrollment', function (): void {
    $ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Portugués']))->create(['numero' => 2, 'nivel' => NivelCurricular::Basico]);
    $grupo = Grupo::factory()->create(['codigo' => 'POR-2-T', 'ciclo_id' => $ciclo->id, 'periodo_id' => Periodo::factory()->create(['nombre' => 'Marzo 2027'])->id]);
    $activa = Matricula::factory()->activa()->create(['persona_id' => $this->ana->id, 'grupo_id' => $grupo->id]);
    $retirada = Matricula::factory()->retirada()->create(['persona_id' => $this->ana->id, 'grupo_id' => $grupo->id]);

    $documento = app(EmitirConstanciaDeMatricula::class)($activa->id, $this->emisor);

    expect($documento)
        ->tipo->toBe(TipoDocumento::ConstanciaMatricula)
        ->matricula_id->toBe($activa->id)
        ->idioma->toBe('Portugués')
        ->nivel->toBe(NivelCurricular::Basico)
        ->and($documento->detalle)->toMatchArray(['grupo' => 'POR-2-T', 'ciclo_numero' => 2, 'periodo' => 'Marzo 2027', 'codigo_matricula' => $activa->ulid])
        ->and(fn () => app(EmitirConstanciaDeMatricula::class)($retirada->id, $this->emisor))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La matrícula en el grupo POR-2-T está retirada; la constancia de matrícula solo se emite para una matrícula activa.');
});

it('issues in bulk the certificates completed in a period, once per student, and the grade records of an acta', function (): void {
    basicoCompleto($this->ana);
    $periodo = Acta::query()->where('ciclo_numero', 4)->sole()->periodo_id;
    ActasFinales::historial(Persona::factory()->create(), 'Inglés', [[1, '14.00', 'aprobado']], periodoId: $periodo);

    $emitidos = app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor);
    $repetidos = app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor);
    $constancias = app(EmitirConstanciasDeNotasDelActa::class)(Acta::query()->where('ciclo_numero', 4)->sole()->id, $this->emisor);
    $constanciasRepetidas = app(EmitirConstanciasDeNotasDelActa::class)(Acta::query()->where('ciclo_numero', 4)->sole()->id, $this->emisor);

    expect(array_map(fn (Documento $documento): int => $documento->persona_id, $emitidos))->toBe([$this->ana->id])
        ->and($repetidos)->toBe([])
        ->and(array_map(fn (Documento $documento): array => [$documento->persona_id, $documento->tipo], $constancias))->toBe([[$this->ana->id, TipoDocumento::ConstanciaNotas]])
        ->and($constanciasRepetidas)->toBe([]);
});

it('voids a document once with a reason and keeps it otherwise unchanged', function (): void {
    basicoCompleto($this->ana);
    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);

    expect(fn () => app(AnularDocumento::class)($documento->id, 'Mal', $this->emisor))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica el motivo de la anulación, de 5 a 500 caracteres.');

    app(AnularDocumento::class)($documento->id, '  Se entregó a otra persona por error.  ', $this->emisor);

    expect($documento->refresh())
        ->estaVigente()->toBeFalse()
        ->motivo_anulacion->toBe('Se entregó a otra persona por error.')
        ->anulado_por->toBe($this->emisor->id)
        ->and(fn () => app(AnularDocumento::class)($documento->id, 'Otra vez por error.', $this->emisor))->toThrow(ReglaDeNegocioIncumplida::class, "El documento {$documento->codigo} ya está anulado.");
});

it('lets only users with the documents permission issue or void', function (): void {
    basicoCompleto($this->ana);
    $ajeno = User::factory()->create();
    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $this->emisor);

    expect(fn () => app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', $ajeno))->toThrow(AuthorizationException::class)
        ->and(fn () => app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $ajeno))->toThrow(AuthorizationException::class)
        ->and(fn () => app(EmitirCertificadosDelPeriodo::class)(1, $ajeno))->toThrow(AuthorizationException::class)
        ->and(fn () => app(AnularDocumento::class)($documento->id, 'Se entregó por error.', $ajeno))->toThrow(AuthorizationException::class)
        ->and(Documento::query()->count())->toBe(1)
        ->and($documento->refresh()->estaVigente())->toBeTrue();
});

it('keeps issued documents locked in the database outside the Actions', function (Closure $cambio, string $mensaje): void {
    basicoCompleto($this->ana);
    app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    expect(fn () => $cambio())->toThrow(QueryException::class, $mensaje);
})->with([
    'changing the grade' => [fn () => DB::table('cer_documentos')->update(['nota_final' => '20.00']), 'un documento emitido solo admite su anulación'],
    'deleting it' => [fn () => DB::table('cer_documentos')->delete(), 'un documento emitido no se elimina'],
    'truncating the table' => [fn () => DB::statement('TRUNCATE cer_documentos'), 'las actas y los documentos emitidos no se borran'],
    'truncating the actas' => [fn () => DB::statement('TRUNCATE cer_actas CASCADE'), 'las actas y los documentos emitidos no se borran'],
    'a second valid certificate' => [fn () => DB::table('cer_documentos')->insert([...collect((array) DB::table('cer_documentos')->first())->except(['id'])->all(), 'codigo' => 'CILN-0000000000']), 'cer_documentos_certificado_vigente_unique'],
]);

it('goes on with the rest of the period when one certificate was issued meanwhile by someone else', function (): void {
    basicoCompleto($this->ana);
    $beto = Persona::factory()->create();
    $periodo = Acta::query()->where('ciclo_numero', 4)->sole()->periodo_id;
    ActasFinales::historial($beto, 'Inglés', [[1, '14.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '14.00', 'aprobado'], [4, '14.00', 'aprobado']], periodoId: $periodo);
    // Simulates another session issuing Ana's certificate between the eligibility check and the insert (the unique index rejects hers).
    Documento::creating(function (Documento $documento): void {
        if ($documento->persona_id === $this->ana->id && ! Documento::query()->where('persona_id', $this->ana->id)->exists()) {
            DB::table('cer_documentos')->insert([...$documento->getAttributes(), 'codigo' => 'CILN-SIMULTANEO', 'detalle' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        }
    });

    Log::spy();

    $emitidos = app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor);

    expect(array_map(fn (Documento $documento): int => $documento->persona_id, $emitidos))->toBe([$beto->id]);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'La persona ya tiene un certificado vigente del nivel Básico de Inglés.'])->once();
});
