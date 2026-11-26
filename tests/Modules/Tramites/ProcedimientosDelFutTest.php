<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Aprobacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeResolucion;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->personal = User::factory()->create()->givePermissionTo([PermisoTramite::Gestionar->value, PermisoCertificacion::Documentos->value]);
});

function tipoPorCodigo(string $codigo): TipoTramite
{
    return TipoTramite::query()->where('codigo', $codigo)->sole();
}

function pedir(Persona $persona, TipoTramite $tipo, DatosDeSolicitud $datos): Tramite
{
    return app(SolicitarTramite::class)($persona->id, $tipo->id, $datos);
}

// A type without fee whose request goes straight to the staff, ready to be finished after one advance.
function listoParaFinalizar(Persona $persona, AccionFinal $accion, DatosDeSolicitud $datos, array $campos = []): Tramite
{
    $tipo = TipoTramite::factory()->sinTasa()->create(['nombre' => $accion->etiqueta(), 'accion_final' => $accion, ...($campos === [] ? [] : ['campos' => $campos])]);
    $tramite = pedir($persona, $tipo, $datos);
    app(AvanzarTramite::class)($tramite->id, User::factory()->create()->givePermissionTo(PermisoTramite::Gestionar->value));

    return $tramite;
}

function matriculaTerminadaEl(Persona $persona, string $idioma, string $fin): Matricula
{
    $ciclo = Ciclo::query()->where(['idioma_id' => ActasFinales::planDeEstudios($idioma)->id, 'numero' => 1])->sole();
    $inicio = CarbonImmutable::parse($fin)->startOfMonth();
    $periodo = Periodo::factory()->create([
        'anio' => $inicio->year, 'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin,
        'matricula_inicio' => $inicio->subDays(15)->toDateString(), 'matricula_fin' => $inicio->addDays(4)->toDateString(),
    ]);

    return Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id])->id]);
}

function enPosgrado(Persona $persona, string $idioma = 'Inglés'): void
{
    $ciclo = Ciclo::query()->where(['idioma_id' => ActasFinales::planDeEstudios($idioma)->id, 'numero' => 1])->sole();
    $grupo = Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'programa_id' => Programa::query()->where('codigo', 'POSGRADO')->value('id')]);
    Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id]);
}

it('ships the FUT procedures as active default types with their fields, programs, prerequisite and stages', function (string $codigo, AccionFinal $accion, array $campos, bool $tasa, bool $posgrado, ?string $previo): void {
    $tipo = tipoPorCodigo($codigo)->load(['programas', 'etapas']);

    expect($tipo)->accion_final->toBe($accion)->activo->toBeTrue()->concepto_id->toBeNull()
        ->and($tipo->campos)->toBe($campos)
        ->and($tipo->cobraTasa())->toBe($tasa)
        ->and($tipo->programas->pluck('programa_id')->all())->toBe($posgrado ? [Programa::query()->where('codigo', 'POSGRADO')->value('id')] : [])
        ->and($tipo->requiere_tipo_previo_id)->toBe($previo === null ? null : tipoPorCodigo($previo)->id)
        ->and($tipo->requisitos)->toContain('Datos del estudiante registrados en su cuenta')->not->toContain('DNI')
        ->and($tipo->etapas->last()?->avance)->toBe(100);
})->with([
    'proficiency' => ['SUFICIENCIA', AccionFinal::Suficiencia, [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]], true, false, null],
    'reclassification' => ['RECLASIFICACION', AccionFinal::Reclasificacion, [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => false]], true, false, null],
    'accreditation' => ['ACREDITACION', AccionFinal::Acreditacion, [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true], ['clave' => 'mencion', 'obligatorio' => true]], true, true, null],
    'postgraduate certificate' => ['CERTIFICADO-POSGRADO', AccionFinal::CertificadoNivel, [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]], true, true, 'ACREDITACION'],
    'half scholarship' => ['MEDIA-BECA', AccionFinal::Resolucion, [['clave' => 'modalidad', 'obligatorio' => true], ['clave' => 'exposicion', 'obligatorio' => true]], false, false, null],
    'other' => ['OTRO', AccionFinal::Resolucion, [['clave' => 'exposicion', 'obligatorio' => true], ['clave' => 'celular', 'obligatorio' => false]], false, false, null],
]);

it('keeps the regular level certificate open to every program and asks the reclassification 2 months away', function (): void {
    expect(tipoPorCodigo('CERTIFICADO-NIVEL')->programas()->count())->toBe(0)
        ->and(tipoPorCodigo('RECLASIFICACION')->meses_sin_estudiar)->toBe(2);
});

it('removes on rollback only the default types nobody requested, and inserts them again', function (): void {
    $migracion = require base_path('app/Modules/Tramites/Database/Migrations/2026_11_29_100300_insert_fut_procedure_types.php');
    pedir($this->persona, tipoPorCodigo('OTRO'), new DatosDeSolicitud(exposicion: 'Solicito copia de mi horario del ciclo.'));

    $migracion->down();

    expect(TipoTramite::query()->whereIn('codigo', ['SUFICIENCIA', 'RECLASIFICACION', 'ACREDITACION', 'CERTIFICADO-POSGRADO', 'MEDIA-BECA'])->count())->toBe(0)
        ->and(TipoTramite::query()->where('codigo', 'OTRO')->exists())->toBeTrue();

    $migracion->up();

    expect(TipoTramite::query()->whereIn('codigo', ['SUFICIENCIA', 'RECLASIFICACION', 'ACREDITACION', 'CERTIFICADO-POSGRADO', 'MEDIA-BECA', 'OTRO'])->count())->toBe(6)
        ->and(tipoPorCodigo('CERTIFICADO-POSGRADO')->requiere_tipo_previo_id)->toBe(tipoPorCodigo('ACREDITACION')->id);
});

it('lets only postgraduate students request the accreditation, for a level with a passed cycle and with the mention', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado']]);
    $acreditacion = tipoPorCodigo('ACREDITACION');
    $datos = new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico', mencionGrado: 'maestria', mencionTexto: 'Educación Superior');

    expect(fn (): Tramite => pedir($this->persona, $acreditacion, $datos))->toThrow(ReglaDeNegocioIncumplida::class, 'Este trámite solo está disponible para el programa Posgrado.');

    enPosgrado($this->persona);

    expect(fn (): Tramite => pedir($this->persona, $acreditacion, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'intermedio', mencionGrado: 'maestria', mencionTexto: 'Educación Superior')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Aún no apruebas ningún ciclo de ese idioma y nivel.')
        ->and(fn (): Tramite => pedir($this->persona, $acreditacion, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la mención: elige el grado');

    expect(pedir($this->persona, $acreditacion, $datos)->datos)->toEqual(['idioma' => 'Inglés', 'nivel' => 'basico', 'mencion_grado' => 'maestria', 'mencion_grado_nombre' => 'Maestría', 'mencion_grado_titulo' => 'Maestro', 'mencion' => 'Educación Superior']);
});

it('asks for a finished accreditation of the same language and level before the postgraduate certificate', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    enPosgrado($this->persona);
    $certificado = tipoPorCodigo('CERTIFICADO-POSGRADO');

    expect(fn (): Tramite => pedir($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Primero debes completar el trámite «Acreditación (posgrado)» para este idioma y nivel.');

    $acreditacion = pedir($this->persona, tipoPorCodigo('ACREDITACION'), new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico', mencionGrado: 'doctorado', mencionTexto: 'Educación'));
    DB::table('tra_tramites')->where('id', $acreditacion->id)->update(['estado' => 'finalizado', 'etapa_actual' => 3, 'finalizado_en' => now()]);

    expect(pedir($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'))->datos)->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'basico']);
});

it('accepts a reclassification only when the last enrollment in the language ended the configured months ago', function (): void {
    $tipo = tipoPorCodigo('RECLASIFICACION');
    $datos = new DatosDeSolicitud(idioma: 'Inglés', nivel: 'intermedio');
    ActasFinales::planDeEstudios('Inglés');

    expect(fn (): Tramite => pedir($this->persona, $tipo, $datos))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La reclasificación es para estudiantes del CILN que dejaron de estudiar: no tienes matrículas en Inglés.');

    matriculaTerminadaEl($this->persona, 'Inglés', '2026-12-31');
    matriculaTerminadaEl($this->persona, 'Inglés', '2027-01-31');
    $this->travelTo(CarbonImmutable::parse('2027-03-30 23:00', 'America/Lima'));

    expect(fn (): Tramite => pedir($this->persona, $tipo, $datos))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La reclasificación de Inglés es para quienes dejaron de estudiar 2 meses o más: podrás pedirla desde el 31/03/2027.');

    $this->travelTo(CarbonImmutable::parse('2027-03-31 08:00', 'America/Lima'));

    expect(pedir($this->persona, $tipo, $datos)->datos)->toEqual(['idioma' => 'Inglés', 'nivel' => 'intermedio']);
});

it('reads the months of the reclassification from its type', function (): void {
    $tipo = tipoPorCodigo('RECLASIFICACION');
    $tipo->update(['meses_sin_estudiar' => 3]);
    matriculaTerminadaEl($this->persona, 'Inglés', '2027-01-31');
    $this->travelTo(CarbonImmutable::parse('2027-04-29 12:00', 'America/Lima'));

    expect(fn (): Tramite => pedir($this->persona, $tipo, new DatosDeSolicitud(idioma: 'Inglés')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'dejaron de estudiar 3 meses o más: podrás pedirla desde el 30/04/2027.');
});

it('records a passed proficiency exam: every cycle of the level by proficiency with the grade, and the next level enabled', function (): void {
    ActasFinales::planDeEstudios('Francés');
    EsquemaEvaluacion::factory()->create(['vigente_desde' => '2020-01-01', 'nota_minima_aprobatoria' => '13.00']);
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Suficiencia, new DatosDeSolicitud(idioma: 'Francés', nivel: 'basico'));

    app(FinalizarTramite::class)($tramite->id, $this->personal, resolucion: new DatosDeResolucion(nota: '15'));

    $aprobaciones = Aprobacion::query()->where('persona_id', $this->persona->id)->orderBy('ciclo_numero')->get();
    $quinto = Ciclo::query()->where(['idioma_id' => ActasFinales::planDeEstudios('Francés')->id, 'numero' => 5])->value('id');

    expect($tramite->refresh())->estado->toBe(EstadoTramite::Finalizado)->resultado->toBe(['nota' => '15.00', 'aprobado' => true, 'nota_minima' => '13.00'])
        ->and($aprobaciones->pluck('ciclo_numero')->all())->toBe([1, 2, 3, 4])
        ->and($aprobaciones->every(fn (Aprobacion $fila): bool => $fila->forma === FormaDeAprobacion::Suficiencia && $fila->nota === '15.00' && $fila->horas_lectivas === null && $fila->origen_id === $tramite->id))->toBeTrue()
        ->and(Habilitacion::query()->where('persona_id', $this->persona->id)->sole())->ciclo_id->toBe($quinto)->origen->toBe(OrigenHabilitacion::Suficiencia)
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->value('comentario'))->toBe('Nota del examen de suficiencia: 15.00 (aprobado; nota mínima 13.00).');
});

it('records a failed proficiency exam with its grade and nothing else, and never lowers an enabled cycle', function (): void {
    $idioma = ActasFinales::planDeEstudios('Francés');
    EsquemaEvaluacion::factory()->create(['vigente_desde' => '2020-01-01']);
    $desaprobado = listoParaFinalizar($this->persona, AccionFinal::Suficiencia, new DatosDeSolicitud(idioma: 'Francés', nivel: 'basico'));

    expect(fn () => app(FinalizarTramite::class)($desaprobado->id, $this->personal, resolucion: new DatosDeResolucion(nota: '21')))->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la nota del examen, de 0 a 20.');

    app(FinalizarTramite::class)($desaprobado->id, $this->personal, resolucion: new DatosDeResolucion(nota: '10.99'));

    expect($desaprobado->refresh())->estado->toBe(EstadoTramite::Finalizado)->resultado->toBe(['nota' => '10.99', 'aprobado' => false, 'nota_minima' => '11.00'])
        ->and(Aprobacion::query()->count())->toBe(0)
        ->and(Habilitacion::query()->count())->toBe(0);

    $avanzado = Ciclo::query()->where(['idioma_id' => $idioma->id, 'numero' => 10])->value('id');
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $idioma->id, 'ciclo_id' => $avanzado]);
    $aprobado = listoParaFinalizar($this->persona, AccionFinal::Suficiencia, new DatosDeSolicitud(idioma: 'Francés', nivel: 'intermedio'));
    app(FinalizarTramite::class)($aprobado->id, $this->personal, resolucion: new DatosDeResolucion(nota: '18'));

    expect(Habilitacion::query()->where('persona_id', $this->persona->id)->sole()->ciclo_id)->toBe($avanzado)
        ->and(Aprobacion::query()->pluck('ciclo_numero')->sort()->values()->all())->toBe([5, 6, 7, 8]);
});

it('refuses to finish a proficiency exam when no evaluation scheme applies, leaving the trámite as it was', function (): void {
    ActasFinales::planDeEstudios('Francés');
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Suficiencia, new DatosDeSolicitud(idioma: 'Francés', nivel: 'basico'));
    $movimientos = Movimiento::query()->where('tramite_id', $tramite->id)->count();

    expect(fn () => app(FinalizarTramite::class)($tramite->id, $this->personal, resolucion: new DatosDeResolucion(nota: '15')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No hay un esquema de evaluación vigente para Francés en el nivel Básico: configúralo para conocer la nota aprobatoria.')
        ->and($tramite->refresh())->estado->toBe(EstadoTramite::EnProceso)->resultado->toBeNull()
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->count())->toBe($movimientos)
        ->and(Aprobacion::query()->count())->toBe(0)
        ->and(Habilitacion::query()->count())->toBe(0);
});

it('assigns the reclassification cycle, lower ones included, and records the skipped cycles like a placement', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado']]);
    $idioma = ActasFinales::planDeEstudios('Inglés');
    $ciclo = fn (int $numero): int => (int) Ciclo::query()->where(['idioma_id' => $idioma->id, 'numero' => $numero])->value('id');
    Habilitacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $idioma->id, 'ciclo_id' => $ciclo(8)]);
    $this->travelTo(CarbonImmutable::parse('2027-06-01 10:00', 'America/Lima'));
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Reclasificacion, new DatosDeSolicitud(idioma: 'Inglés'));

    expect(fn () => app(FinalizarTramite::class)($tramite->id, $this->personal, resolucion: new DatosDeResolucion(cicloId: Ciclo::factory()->create()->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Elige un ciclo de Inglés.');

    app(FinalizarTramite::class)($tramite->id, $this->personal, resolucion: new DatosDeResolucion(cicloId: $ciclo(4)));

    expect(Habilitacion::query()->where('persona_id', $this->persona->id)->sole())->ciclo_id->toBe($ciclo(4))->origen->toBe(OrigenHabilitacion::Reclasificacion)
        ->and(Aprobacion::query()->where(['origen_tipo' => 'tramite', 'origen_id' => $tramite->id, 'forma' => FormaDeAprobacion::Clasificacion])->whereNull('nota')->orderBy('ciclo_numero')->pluck('ciclo_numero')->all())->toBe([2, 3])
        ->and($tramite->refresh()->resultado)->toBe(['ciclo' => 'Ciclo 4 (Básico)', 'ciclo_id' => $ciclo(4)]);
});

it('finishes a half scholarship or other request only with the staff answer, which the trámite keeps', function (): void {
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Resolucion, new DatosDeSolicitud(modalidad: 'Ciclo regular', exposicion: 'Solicito media beca por mi situación económica.'), [['clave' => 'modalidad', 'obligatorio' => true], ['clave' => 'exposicion', 'obligatorio' => true]]);

    expect(fn () => app(FinalizarTramite::class)($tramite->id, $this->personal))->toThrow(ReglaDeNegocioIncumplida::class, 'Escribe la respuesta al estudiante, entre 5 y 500 caracteres.')
        ->and($tramite->refresh()->estado)->toBe(EstadoTramite::EnProceso);

    app(FinalizarTramite::class)($tramite->id, $this->personal, resolucion: new DatosDeResolucion(comentario: '  Se otorga la media beca para el ciclo de mayo.  '));

    expect($tramite->refresh())->estado->toBe(EstadoTramite::Finalizado)->resultado->toBe(['comentario' => 'Se otorga la media beca para el ciclo de mayo.'])
        ->and($tramite->datos)->toEqual(['modalidad' => 'Ciclo regular', 'exposicion' => 'Solicito media beca por mi situación económica.'])
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->value('comentario'))->toBe('Se otorga la media beca para el ciclo de mayo.');
});

it('issues the accreditation record with the degree mention when finishing an accreditation', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '17.00', 'aprobado']]);
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Acreditacion, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico', mencionGrado: 'maestria', mencionTexto: 'Educación Superior'));

    app(FinalizarTramite::class)($tramite->id, $this->personal);

    $documento = Documento::query()->findOrFail($tramite->refresh()->documento_id);

    expect($tramite->estado)->toBe(EstadoTramite::Finalizado)
        ->and($documento->tipo->value)->toBe('constancia_acreditacion')
        ->and($documento->detalle['mencion'])->toBe('Maestría en Educación Superior')
        ->and(array_column($documento->detalle['filas'], 'nota'))->toBe(['15.00', '17.00']);
});

it('keeps finishing the existing types without staff data, as before', function (): void {
    $tramite = listoParaFinalizar($this->persona, AccionFinal::Ninguna, new DatosDeSolicitud);

    app(FinalizarTramite::class)($tramite->id, $this->personal);

    expect($tramite->refresh())->estado->toBe(EstadoTramite::Finalizado)->resultado->toBeNull()->documento_id->toBeNull()
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->value('comentario'))->toBeNull();
});
