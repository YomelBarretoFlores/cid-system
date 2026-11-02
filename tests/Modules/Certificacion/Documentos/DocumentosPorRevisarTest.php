<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\AnularDocumento;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeMatricula;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

const MOTIVO_DE_REVISION = 'Se rectificó el acta final del grupo ING-1-M (Básico I, Marzo 2027) y cambió la nota o la condición del estudiante. Revisar el documento y, si ya no corresponde, anularlo y emitir otro.';

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->ana = ActasFinales::estudiante($this->curso['grupo'], 'Alva', '70000001');
    $this->beto = ActasFinales::estudiante($this->curso['grupo'], 'Bravo', '70000002');
    ActasFinales::preActa($this->ana, [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00', $this->curso['participacion']->id => '18.00']);
    ActasFinales::preActa($this->beto, [$this->curso['parcial']->id => '14.00', $this->curso['final']->id => '14.00', $this->curso['participacion']->id => '14.00']);
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);
    ActasFinales::historial(Persona::query()->findOrFail($this->ana->persona_id), 'Inglés', [[2, '15.00', 'aprobado'], [3, '16.00', 'aprobado'], [4, '13.00', 'aprobado']]);
    $this->acta = Acta::query()->where('grupo_id', $this->curso['grupo']->id)->sole();
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
    $this->travelTo(CarbonImmutable::parse('2027-04-10 11:30', 'America/Lima'));

    $this->constanciaDeAna = app(EmitirConstanciaDeNotas::class)($this->ana->persona_id, 'Inglés', $this->gestor);
    $this->certificadoDeAna = app(EmitirCertificadoDeNivel::class)($this->ana->persona_id, 'Inglés', NivelCurricular::Basico, $this->gestor);
    $this->matriculaDeAna = app(EmitirConstanciaDeMatricula::class)($this->ana->id, $this->gestor);
    $this->anuladaDeAna = app(EmitirConstanciaDeNotas::class)($this->ana->persona_id, 'Inglés', $this->gestor);
    app(AnularDocumento::class)($this->anuladaDeAna->id, 'Se emitió dos veces.', $this->gestor);
    $this->constanciaDeBeto = app(EmitirConstanciaDeNotas::class)($this->beto->persona_id, 'Inglés', $this->gestor);
});

function rectificarYCerrar(array ...$cambios): void
{
    app(AutorizarRectificacion::class)(test()->acta->id, 'Error al transcribir el examen final.', test()->gestor);
    test()->travelTo(CarbonImmutable::parse('2027-05-10 09:00', 'America/Lima'));

    foreach ($cambios as $notas) {
        app(RectificarNotasFinales::class)(test()->curso['grupo']->id, test()->curso['docente']->id, $notas);
    }

    app(CerrarActa::class)(test()->curso['grupo']->id, test()->curso['docente']->id, test()->curso['usuario']);
}

it('flags the valid grade records and certificates of a student whose result changed when the rectified acta closes again', function (): void {
    rectificarYCerrar([$this->ana->id => [$this->curso['final']->id => '0']]);

    $marcados = Documento::query()->whereNotNull('revisar_desde')->orderBy('id')->get();

    expect($marcados->modelKeys())->toBe([$this->constanciaDeAna->id, $this->certificadoDeAna->id])
        ->and($marcados->pluck('motivo_revision')->unique()->all())->toBe([MOTIVO_DE_REVISION])
        ->and($marcados->every(fn (Documento $documento): bool => $documento->revisar_desde?->setTimezone('America/Lima')->format('Y-m-d H:i') === '2027-05-10 09:00'))->toBeTrue()
        ->and($marcados->every(fn (Documento $documento): bool => $documento->estaPorRevisar() && $documento->estaVigente()))->toBeTrue()
        ->and($this->matriculaDeAna->refresh()->estaPorRevisar())->toBeFalse()
        ->and($this->anuladaDeAna->refresh()->revisar_desde)->toBeNull()
        ->and($this->constanciaDeBeto->refresh()->revisar_desde)->toBeNull();

    $this->get(route('verificacion.documento', $this->constanciaDeAna->codigo))->assertOk()->assertSee('Documento auténtico y vigente')->assertDontSee('revisar');
});

it('flags nothing when the rectification leaves every final grade and state as it was', function (): void {
    rectificarYCerrar([$this->ana->id => [$this->curso['final']->id => '0']], [$this->ana->id => [$this->curso['final']->id => '12']]);

    expect(Documento::query()->whereNotNull('revisar_desde')->count())->toBe(0);
});

it('shows the documents to review in the panel list, its filter and the document page, and stops once voided', function (): void {
    rectificarYCerrar([$this->ana->id => [$this->curso['final']->id => '0']]);
    $this->actingAs($this->gestor);

    Livewire::test(ListDocumentos::class)
        ->assertTableColumnStateSet('revision', 'Por revisar', $this->constanciaDeAna)
        ->assertTableColumnStateNotSet('revision', 'Por revisar', $this->constanciaDeBeto)
        ->filterTable('por_revisar')
        ->assertCanSeeTableRecords([$this->constanciaDeAna, $this->certificadoDeAna])
        ->assertCanNotSeeTableRecords([$this->constanciaDeBeto, $this->matriculaDeAna, $this->anuladaDeAna]);

    $this->get(DocumentoResource::getUrl('view', ['record' => $this->constanciaDeAna]))
        ->assertOk()
        ->assertSeeInOrder(['Vigente', 'Por revisar', MOTIVO_DE_REVISION, '10/05/2027 09:00']);

    app(AnularDocumento::class)($this->constanciaDeAna->id, 'Notas rectificadas; se emite otra.', $this->gestor);

    Livewire::test(ListDocumentos::class)
        ->assertTableColumnStateNotSet('revision', 'Por revisar', $this->constanciaDeAna)
        ->filterTable('por_revisar')
        ->assertCanSeeTableRecords([$this->certificadoDeAna])
        ->assertCanNotSeeTableRecords([$this->constanciaDeAna]);
    $this->get(DocumentoResource::getUrl('view', ['record' => $this->constanciaDeAna]))->assertOk()->assertDontSee(MOTIVO_DE_REVISION);
});

it('lets the database change only the review flag of a valid document, besides voiding it', function (Closure $cambio, string $mensaje): void {
    expect(fn (): mixed => $cambio->call($this))->toThrow(QueryException::class, $mensaje);
})->with([
    'another column with the flag' => [fn () => DB::table('cer_documentos')->where('id', $this->constanciaDeAna->id)->update(['revisar_desde' => now(), 'motivo_revision' => 'Revisar.', 'idioma' => 'Francés']), 'un documento emitido solo admite su anulación'],
    'the flag of a voided document' => [fn () => DB::table('cer_documentos')->where('id', $this->anuladaDeAna->id)->update(['revisar_desde' => now(), 'motivo_revision' => 'Revisar.']), 'un documento emitido solo admite su anulación'],
    'a flag without its reason' => [fn () => DB::table('cer_documentos')->where('id', $this->constanciaDeAna->id)->update(['revisar_desde' => now()]), 'cer_documentos_revision_check'],
]);

it('accepts the review flag alone on a valid document', function (): void {
    DB::table('cer_documentos')->where('id', $this->constanciaDeBeto->id)->update(['revisar_desde' => now(), 'motivo_revision' => 'Revisar.']);

    expect($this->constanciaDeBeto->refresh()->estaPorRevisar())->toBeTrue();
});
