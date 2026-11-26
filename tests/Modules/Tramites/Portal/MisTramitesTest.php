<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Programa;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Livewire\MisTramites;
use App\Modules\Tramites\Models\Adjunto;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    $this->constancia = TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->sole();
});

it('lists the student\'s trámites in a table with their code, stage, state and observation, and badges the observed ones in the menu', function (): void {
    $observado = Tramite::factory()->for($this->constancia)->create(['persona_id' => $this->persona->id, 'etapa_actual' => 1]);
    DB::table('tra_tramites')->where('id', $observado->id)->update(['estado' => 'observado', 'observacion' => 'El recibo no se lee. Sube una foto más nítida.']);
    $ajeno = Tramite::factory()->for($this->constancia)->create();

    $this->actingAs($this->user)->get(route('estudiante.tramites'))
        ->assertOk()
        ->assertSeeTextInOrder(['Mis trámites', 'Código', 'Trámite', 'Iniciado el', 'Etapa actual', 'Estado', 'Documento',
            $observado->refresh()->codigo, 'Constancia de matrícula', $observado->created_at->timezone('America/Lima')->format('d/m/Y'), 'Pago validado', 'Observado', '—',
            'El recibo no se lee. Sube una foto más nítida.', 'Ver etapas', 'Nuevo trámite'])
        ->assertSeeText('Etapa 2 de 4: Pago validado')
        ->assertSeeText('33 % de avance')
        ->assertSeeTextInOrder(['Trámites', '1'])
        ->assertDontSeeText($ajeno->refresh()->codigo);
});

it('starts a trámite chosen from the list of types with the data its final action asks for', function (): void {
    $this->actingAs($this->user);

    Livewire::test(MisTramites::class)
        ->assertSeeTextInOrder(['Nuevo trámite', 'Trámite', 'Tasa', 'Constancia de matrícula', 'Constancia', 'Elegir'])
        ->assertSeeHtml('wire:click="elegir('.$this->constancia->id.')"')
        ->assertDontSeeText('Elige tu matrícula')
        ->call('elegir', $this->constancia->id)
        ->assertSeeText('Constancia de matrícula')
        ->assertSeeText('Requisitos: Una matrícula activa y el pago de la tasa.')
        ->assertSeeText('Elige tu matrícula')
        ->assertSeeText('Este trámite tiene una tasa (Constancia).')
        ->set('matricula', (string) $this->matricula->id)
        ->call('solicitar')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.tramites'));

    $tramite = Tramite::query()->where('persona_id', $this->persona->id)->sole();
    expect($tramite)->estado->toBe(EstadoTramite::PendientePago)
        ->and(session('estado'))->toContain("Registramos tu trámite {$tramite->codigo}. Paga la tasa");
});

it('requests a level certificate from the levels the student completed', function (): void {
    $this->actingAs($this->user);
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '13.00', 'aprobado'], [4, '16.00', 'aprobado']]);
    $constancia = app(SolicitarTramite::class)($this->persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->value('id'), new DatosDeSolicitud(idioma: 'Inglés', nivel: 'basico'));
    DB::table('tra_tramites')->where('id', $constancia->id)->update(['estado' => 'finalizado', 'etapa_actual' => 3, 'finalizado_en' => now()]);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'CERTIFICADO-NIVEL')->value('id'))
        ->assertSeeText('Inglés · Básico')
        ->set('nivel', 'Inglés|basico')
        ->call('solicitar')
        ->assertHasNoErrors();

    expect(Tramite::query()->where(['persona_id' => $this->persona->id, 'tipo_nombre' => 'Certificado de culminación de nivel'])->sole()->datos)->toMatchArray(['idioma' => 'Inglés', 'nivel' => 'basico']);
});

it('requests a grade record choosing among the levels with a passed cycle', function (): void {
    $this->actingAs($this->user);
    ActasFinales::historial($this->persona, 'Italiano', [[1, '15.00', 'aprobado'], [5, '08.00', 'desaprobado']]);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->value('id'))
        ->assertSeeText('Idioma y nivel')
        ->assertSeeText('Italiano · Básico')
        ->assertDontSeeText('Italiano · Intermedio')
        ->set('nivel', 'Italiano|basico')
        ->call('solicitar')
        ->assertHasNoErrors();

    expect(Tramite::query()->where('persona_id', $this->persona->id)->sole()->datos)->toMatchArray(['idioma' => 'Italiano', 'nivel' => 'basico']);
});

it('shows why a request is refused and keeps the form open', function (): void {
    $this->actingAs($this->user);

    Livewire::test(MisTramites::class)
        ->call('elegir', TipoTramite::query()->where('codigo', 'RETIRO')->value('id'))
        ->set('matricula', (string) $this->matricula->id)
        ->set('motivo', 'no')
        ->call('solicitar')
        ->assertHasErrors('solicitud')
        ->assertSeeText('Indica el motivo, entre 5 y 500 caracteres.')
        ->assertSeeText('Retiro del ciclo');

    expect(Tramite::query()->count())->toBe(0);
});

it('ignores a type that is not available and does not let the browser choose it', function (): void {
    $this->actingAs($this->user);
    $inactivo = TipoTramite::factory()->sinTasa()->create(['activo' => false]);

    Livewire::test(MisTramites::class)->call('elegir', $inactivo->id)->assertSet('tipoElegido', null)->call('solicitar');

    expect(Tramite::query()->count())->toBe(0)
        ->and(fn () => Livewire::test(MisTramites::class)->set('tipoElegido', $this->constancia->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('sends guests to the portal sign-in', function (): void {
    $this->get(route('estudiante.tramites'))->assertRedirect(route('ingresar'));
});

it('renders the FUT fields a type configures and sends them with the attachments', function (): void {
    Storage::fake('local');
    $this->actingAs($this->user);
    $tipo = TipoTramite::factory()->sinTasa()->create([
        'nombre' => 'Otro', 'accion_final' => AccionFinal::Ninguna,
        'campos' => [['clave' => 'exposicion', 'obligatorio' => true], ['clave' => 'celular', 'obligatorio' => false]],
        'adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]],
    ]);

    Livewire::test(MisTramites::class)
        ->call('elegir', $tipo->id)
        ->assertSeeTextInOrder(['Y expongo que', 'Celular (opcional)', 'Copia de DNI'])
        ->assertDontSeeText('Elige tu matrícula')
        ->set('exposicion', 'Solicito una constancia de estudios para mi trabajo.')
        ->set('celular', '987654321')
        ->set('adjuntos.0', UploadedFile::fake()->createWithContent('dni.pdf', "%PDF-1.4\n%%EOF\n"))
        ->call('solicitar')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.tramites'));

    $tramite = Tramite::query()->where('persona_id', $this->persona->id)->sole();
    expect($tramite->datos)->toEqual(['exposicion' => 'Solicito una constancia de estudios para mi trabajo.', 'celular' => '987654321'])
        ->and(Adjunto::query()->where('tramite_id', $tramite->id)->count())->toBe(1);
});

it('hides the types restricted to programs the student is not in', function (): void {
    $this->actingAs($this->user);
    $tipo = TipoTramite::factory()->sinTasa()->create(['nombre' => 'Acreditación de posgrado', 'accion_final' => AccionFinal::Ninguna]);
    $tipo->programas()->create(['programa_id' => Programa::factory()->create(['nombre' => 'Posgrado de prueba'])->id]);

    Livewire::test(MisTramites::class)->assertSeeText('Constancia de matrícula')->assertDontSeeText('Acreditación de posgrado');
});

it('prints the receipt of the student\'s own trámite only', function (): void {
    Storage::fake('local');
    $tipo = TipoTramite::factory()->sinTasa()->create([
        'nombre' => 'Otro', 'accion_final' => AccionFinal::Ninguna,
        'campos' => [['clave' => 'exposicion', 'obligatorio' => true]],
        'adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]],
    ]);
    $tramite = app(SolicitarTramite::class)($this->persona->id, $tipo->id, new DatosDeSolicitud(
        exposicion: 'Solicito una constancia de estudios para mi trabajo.',
        adjuntos: [UploadedFile::fake()->createWithContent('dni.pdf', "%PDF-1.4\n%%EOF\n")],
    ));
    $ajeno = Tramite::factory()->for($this->constancia)->create()->refresh();

    $this->actingAs($this->user)->get(route('estudiante.tramites.cargo', $tramite->codigo))
        ->assertOk()
        ->assertSeeTextInOrder(['Cargo de trámite', $tramite->codigo, 'Otro', $tramite->created_at->timezone('America/Lima')->format('d/m/Y H:i'),
            'Y expongo que', 'Solicito una constancia de estudios para mi trabajo.', 'Adjuntos', '1']);

    $this->get(route('estudiante.tramites.cargo', $ajeno->codigo))->assertNotFound();
});
