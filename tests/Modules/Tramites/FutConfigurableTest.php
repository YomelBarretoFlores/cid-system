<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Programa;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Models\Adjunto;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
});

function tipoDelFut(array $atributos = []): TipoTramite
{
    return TipoTramite::factory()->sinTasa()->create(['nombre' => 'Otro', 'accion_final' => AccionFinal::Ninguna, ...$atributos]);
}

function solicitarDelFut(Persona $persona, TipoTramite $tipo, DatosDeSolicitud $datos = new DatosDeSolicitud): Tramite
{
    return app(SolicitarTramite::class)($persona->id, $tipo->id, $datos);
}

function finalizarEnBase(Tramite $tramite): void
{
    DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'finalizado', 'etapa_actual' => count($tramite->etapas) - 1, 'finalizado_en' => now()]);
}

function pdfDePrueba(string $nombre = 'dni.pdf', string $relleno = ''): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nombre, "%PDF-1.4\n% {$nombre}\n{$relleno}\n%%EOF\n");
}

function matricularEnPrograma(Persona $persona, Programa $programa): Matricula
{
    return Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => Grupo::factory()->create(['programa_id' => $programa->id])->id]);
}

it('keeps the fields, prerequisite and attachments of the shipped types after the FUT configuration arrives', function (string $codigo, array $campos, ?string $previo): void {
    $tipo = TipoTramite::query()->where('codigo', $codigo)->sole();

    expect($tipo->campos)->toBe($campos)
        ->and($tipo->adjuntos)->toBe([])
        ->and($tipo->requiere_tipo_previo_id)->toBe($previo === null ? null : TipoTramite::query()->where('codigo', $previo)->value('id'));
})->with([
    'enrollment record' => ['CONSTANCIA-MATRICULA', [['clave' => 'matricula', 'obligatorio' => true]], null],
    'grade record' => ['CONSTANCIA-NOTAS', [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]], null],
    'level certificate' => ['CERTIFICADO-NIVEL', [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]], 'CONSTANCIA-NOTAS'],
    'transfer' => ['TRASLADO', [['clave' => 'matricula', 'obligatorio' => true], ['clave' => 'motivo', 'obligatorio' => true]], null],
    'withdrawal' => ['RETIRO', [['clave' => 'matricula', 'obligatorio' => true], ['clave' => 'motivo', 'obligatorio' => true]], null],
]);

it('still accepts the request each shipped type took before, with the same inputs and stored keys', function (string $codigo, Closure $datos, array $esperado): void {
    $matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    if ($codigo === 'CERTIFICADO-NIVEL') {
        finalizarEnBase(solicitarDelFut($this->persona, TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->sole(), new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico')));
    }

    $tramite = solicitarDelFut($this->persona, TipoTramite::query()->where('codigo', $codigo)->sole(), $datos($matricula));

    expect(array_keys($tramite->datos))->toEqualCanonicalizing($esperado);
})->with([
    'enrollment record' => ['CONSTANCIA-MATRICULA', fn (Matricula $matricula): DatosDeSolicitud => new DatosDeSolicitud(matriculaId: $matricula->id), ['matricula_id', 'matricula']],
    'grade record' => ['CONSTANCIA-NOTAS', fn (): DatosDeSolicitud => new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'), ['idioma', 'nivel']],
    'level certificate' => ['CERTIFICADO-NIVEL', fn (): DatosDeSolicitud => new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'), ['idioma', 'nivel']],
    'transfer' => ['TRASLADO', fn (Matricula $matricula): DatosDeSolicitud => new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de turno en el trabajo'), ['matricula_id', 'matricula', 'motivo']],
    'withdrawal' => ['RETIRO', fn (Matricula $matricula): DatosDeSolicitud => new DatosDeSolicitud(matriculaId: $matricula->id, motivo: 'Cambio de turno en el trabajo'), ['matricula_id', 'matricula', 'motivo']],
]);

it('stores the exposition and phone number of a configured free request and validates them', function (): void {
    $tipo = tipoDelFut(['campos' => [['clave' => 'exposicion', 'obligatorio' => true], ['clave' => 'celular', 'obligatorio' => false]]]);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(exposicion: 'Muy corto')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Escribe lo que expones, entre 10 y 1000 caracteres.')
        ->and(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(exposicion: 'Solicito una media beca por mi situación económica.', celular: '12345')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indica un celular de 9 dígitos que empiece con 9.')
        ->and(Tramite::query()->count())->toBe(0);

    $tramite = solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(exposicion: '  Solicito una media beca por mi situación económica.  ', celular: '987 654 321'));
    $sinCelular = solicitarDelFut(Persona::factory()->create(), $tipo, new DatosDeSolicitud(exposicion: 'Solicito una media beca por mi situación económica.'));

    expect($tramite->datos)->toEqual(['exposicion' => 'Solicito una media beca por mi situación económica.', 'celular' => '987654321'])
        ->and($sinCelular->datos)->toEqual(['exposicion' => 'Solicito una media beca por mi situación económica.']);
});

it('asks the mention of the degree as master or doctorate with its specialty', function (): void {
    $tipo = tipoDelFut(['campos' => [['clave' => 'mencion', 'obligatorio' => true]]]);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(mencionGrado: 'bachiller', mencionTexto: 'Educación')))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la mención: elige el grado y escribe su especialidad en hasta 150 caracteres.')
        ->and(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(mencionGrado: 'maestria', mencionTexto: str_repeat('a', 151))))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Indica la mención: elige el grado y escribe su especialidad en hasta 150 caracteres.');

    expect(solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(mencionGrado: 'doctorado', mencionTexto: ' Educación Superior '))->datos)
        ->toEqual(['mencion_grado' => 'doctorado', 'mencion_grado_nombre' => 'Doctorado', 'mencion' => 'Educación Superior']);
});

it('asks for a finished trámite of the previous type for the same language and level', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado'], [5, '15.00', 'aprobado']]);
    $constancia = TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->sole();
    $certificado = TipoTramite::query()->where('codigo', 'CERTIFICADO-NIVEL')->sole();
    $mensaje = 'Primero debes completar el trámite «Constancia de notas» para este idioma y nivel.';

    expect(fn (): Tramite => solicitarDelFut($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico')))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);

    $enCurso = solicitarDelFut($this->persona, $constancia, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'));
    $deOtroNivel = solicitarDelFut($this->persona, $constancia, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'intermedio'));
    finalizarEnBase($deOtroNivel);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico')))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);

    finalizarEnBase($enCurso);

    expect(solicitarDelFut($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'))->datos)->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'basico']);
});

it('lets only students of the chosen programs request a restricted type, and records the program they pick', function (): void {
    $posgrado = Programa::factory()->create(['nombre' => 'Posgrado de prueba']);
    $tipo = tipoDelFut(['campos' => [['clave' => 'programa', 'obligatorio' => true]]]);
    $tipo->programas()->create(['programa_id' => $posgrado->id]);
    matricularEnPrograma($this->persona, Programa::factory()->create(['nombre' => 'Jóvenes']));

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(programaId: $posgrado->id)))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Este trámite solo está disponible para el programa Posgrado de prueba.');

    $alumno = Persona::factory()->create();
    matricularEnPrograma($alumno, $posgrado);

    expect(solicitarDelFut($alumno, $tipo, new DatosDeSolicitud(programaId: $posgrado->id))->datos)->toEqual(['programa_id' => $posgrado->id, 'programa' => 'Posgrado de prueba']);
});

it('stores the required and optional attachments on the private disk with their fingerprint', function (): void {
    Storage::fake('local');
    $tipo = tipoDelFut(['adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true], ['nombre' => 'Fotografía', 'obligatorio' => false]]]);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo))->toThrow(ReglaDeNegocioIncumplida::class, 'Adjunta el archivo «Copia de DNI».');

    $archivo = pdfDePrueba();
    $tramite = solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(adjuntos: [0 => $archivo]));
    $adjunto = Adjunto::query()->where('tramite_id', $tramite->id)->sole();

    expect($adjunto)->nombre->toBe('Copia de DNI')->mime->toBe('application/pdf')
        ->sha256->toBe(hash_file('sha256', $archivo->getRealPath()))->bytes->toBe($archivo->getSize())
        ->and($adjunto->path)->toEndWith('.pdf')
        ->and(Storage::disk('local')->exists($adjunto->path))->toBeTrue();
});

it('refuses an attachment that is not a PDF or image, or weighs more than 5 MB, and stores nothing', function (UploadedFile $archivo, string $mensaje): void {
    Storage::fake('local');
    $tipo = tipoDelFut(['adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]]]);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(adjuntos: [0 => $archivo])))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(Tramite::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'script' => [fn (): UploadedFile => UploadedFile::fake()->createWithContent('dni.pdf', '<?php echo "no";'), 'El adjunto «Copia de DNI» debe ser un PDF o una imagen JPG o PNG.'],
    'too big' => [fn (): UploadedFile => pdfDePrueba('dni.pdf', str_repeat('a', 5 * 1024 * 1024)), 'El adjunto «Copia de DNI» no debe pesar más de 5 MB.'],
]);

it('keeps attachments insert-only', function (): void {
    Storage::fake('local');
    $tramite = solicitarDelFut($this->persona, tipoDelFut(['adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]]]), new DatosDeSolicitud(adjuntos: [pdfDePrueba()]));
    $adjunto = Adjunto::query()->where('tramite_id', $tramite->id)->sole();

    expect(fn () => DB::transaction(fn () => DB::table('tra_adjuntos')->where('id', $adjunto->id)->update(['nombre' => 'Otro'])))->toThrow(QueryException::class, 'los adjuntos de un trámite no se modifican')
        ->and(fn () => DB::transaction(fn () => DB::table('tra_adjuntos')->where('id', $adjunto->id)->delete()))->toThrow(QueryException::class, 'los adjuntos de un trámite no se modifican');
});

it('takes attachments up to the size the system parameters set', function (): void {
    Storage::fake('local');
    app(GuardarParametrosDelSistema::class)(['adjunto_maximo_mb' => 8], User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value));
    $tipo = tipoDelFut(['adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]]]);

    expect(fn (): Tramite => solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(adjuntos: [0 => pdfDePrueba('dni.pdf', str_repeat('a', 8 * 1024 * 1024))])))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El adjunto «Copia de DNI» no debe pesar más de 8 MB.');

    $tramite = solicitarDelFut($this->persona, $tipo, new DatosDeSolicitud(adjuntos: [0 => pdfDePrueba('dni.pdf', str_repeat('a', 6 * 1024 * 1024))]));
    expect(Adjunto::query()->where('tramite_id', $tramite->id)->sole()->bytes)->toBeGreaterThan(6 * 1024 * 1024);
});
