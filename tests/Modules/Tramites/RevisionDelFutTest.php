<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Programa;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
});

function solicitudDeRevision(Persona $persona, TipoTramite $tipo, DatosDeSolicitud $datos): Tramite
{
    return app(SolicitarTramite::class)($persona->id, $tipo->id, $datos);
}

it('accepts a level-less grade record requested before levels existed as the certificate prerequisite of every level', function (): void {
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado'],
        [5, '15.00', 'aprobado'], [6, '15.00', 'aprobado'], [7, '15.00', 'aprobado'], [8, '15.00', 'aprobado']]);
    $constancia = TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->sole();
    $antigua = Tramite::factory()->for($constancia)->create(['persona_id' => $this->persona->id, 'accion_final' => AccionFinal::ConstanciaNotas, 'datos' => ['idioma' => 'Inglés']]);
    DB::table('tra_tramites')->where('id', $antigua->id)->update(['estado' => 'finalizado', 'etapa_actual' => count($antigua->etapas) - 1, 'finalizado_en' => now()]);
    $certificado = TipoTramite::query()->where('codigo', 'CERTIFICADO-NIVEL')->sole();

    expect(solicitudDeRevision($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'))->datos)->toMatchArray(['nivel' => 'basico'])
        ->and(solicitudDeRevision($this->persona, $certificado, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'intermedio'))->datos)->toMatchArray(['nivel' => 'intermedio']);
});

it('offers the program of the student\'s latest enrollment in each language', function (): void {
    $jovenes = Programa::factory()->create(['nombre' => 'Jóvenes de prueba']);
    $posgrado = Programa::factory()->create(['nombre' => 'Posgrado de prueba']);
    $ciclo = ActasFinales::planDeEstudios('Inglés')->ciclos()->where('numero', 1)->sole();
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'programa_id' => $jovenes->id])->id, 'matriculado_en' => now()->subMonths(3)]);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['ciclo_id' => $ciclo->id, 'programa_id' => $posgrado->id])->id, 'matriculado_en' => now()->subMonth()]);
    $tipo = TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Ninguna, 'campos' => [['clave' => 'programa', 'obligatorio' => true]]]);

    expect(fn (): Tramite => solicitudDeRevision($this->persona, $tipo, new DatosDeSolicitud(programaId: $jovenes->id)))->toThrow(ReglaDeNegocioIncumplida::class, 'Elige uno de tus programas.')
        ->and(solicitudDeRevision($this->persona, $tipo, new DatosDeSolicitud(programaId: $posgrado->id))->datos)->toEqual(['programa_id' => $posgrado->id, 'programa' => 'Posgrado de prueba']);
});

it('names the language and level in the prerequisite refusal only when the type asks them', function (array $campos, string $mensaje): void {
    $previo = TipoTramite::factory()->sinTasa()->create(['nombre' => 'Entrevista', 'accion_final' => AccionFinal::Ninguna]);
    $tipo = TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Ninguna, 'campos' => $campos, 'requiere_tipo_previo_id' => $previo->id]);
    ActasFinales::planDeEstudios('Inglés');

    expect(fn (): Tramite => solicitudDeRevision($this->persona, $tipo, new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico', exposicion: 'Solicito una revisión de mi caso.')))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'neither' => [[['clave' => 'exposicion', 'obligatorio' => true]], 'Primero debes completar el trámite «Entrevista».'],
    'language only' => [[['clave' => 'idioma', 'obligatorio' => true]], 'Primero debes completar el trámite «Entrevista» para este idioma.'],
    'language and level' => [[['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]], 'Primero debes completar el trámite «Entrevista» para este idioma y nivel.'],
]);

it('shows the student the outcome staff recorded and asks the modality of a half scholarship', function (): void {
    $user = User::factory()->create();
    $this->persona->update(['user_id' => $user->id]);
    $beca = TipoTramite::query()->where('codigo', 'MEDIA-BECA')->sole();
    $resuelto = Tramite::factory()->for($beca)->create(['persona_id' => $this->persona->id, 'accion_final' => AccionFinal::Resolucion]);
    DB::table('tra_tramites')->where('id', $resuelto->id)->update(['estado' => 'finalizado', 'etapa_actual' => count($resuelto->etapas) - 1, 'finalizado_en' => now(), 'resultado' => json_encode(['comentario' => 'Se otorga la media beca para mayo.'])]);
    $examen = Tramite::factory()->for($beca)->create(['persona_id' => $this->persona->id, 'accion_final' => AccionFinal::Suficiencia]);
    DB::table('tra_tramites')->where('id', $examen->id)->update(['estado' => 'finalizado', 'etapa_actual' => count($examen->etapas) - 1, 'finalizado_en' => now(), 'resultado' => json_encode(['nota' => '15.00', 'aprobado' => true, 'nota_minima' => '11.00'])]);
    $this->actingAs($user);

    Livewire::test(MisTramites::class)
        ->assertSeeText('Respuesta del CILN: Se otorga la media beca para mayo.')
        ->assertSeeText('Nota del examen: 15.00 · Aprobado (nota mínima 11.00)')
        ->call('elegir', $beca->id)
        ->assertSeeText('Modalidad')
        ->set('modalidad', 'Ciclo regular')
        ->set('exposicion', 'Solicito media beca por mi situación económica.')
        ->call('solicitar')
        ->assertHasNoErrors();

    expect(Tramite::query()->where('persona_id', $this->persona->id)->latest('id')->first()?->datos)->toEqual(['modalidad' => 'Ciclo regular', 'exposicion' => 'Solicito media beca por mi situación económica.']);
});
