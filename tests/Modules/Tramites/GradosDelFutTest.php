<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeAcreditacion;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\FinalizarTramite;
use App\Modules\Tramites\Actions\GuardarGradoFut;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\GradosFut\GradoFutResource;
use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\CreateGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\EditGradoFut;
use App\Modules\Tramites\Filament\Resources\GradosFut\Pages\ListGradosFut;
use App\Modules\Tramites\Models\GradoFut;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\LecturaDelFut;
use App\Modules\Tramites\Support\PresentacionDelFut;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->configurador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
});

function pedirMencion(Persona $persona, string $grado, AccionFinal $accion = AccionFinal::Ninguna, array $datos = []): Tramite
{
    $nivel = $accion === AccionFinal::Acreditacion ? [['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]] : [];
    $tipo = TipoTramite::factory()->sinTasa()->create(['nombre' => 'Mención '.$accion->value, 'accion_final' => $accion, 'campos' => [...$nivel, ['clave' => 'mencion', 'obligatorio' => true]]]);

    return app(SolicitarTramite::class)($persona->id, $tipo->id, new DatosDeSolicitud(...[...$datos, 'mencionGrado' => $grado, 'mencionTexto' => 'Gestión Pública']));
}

function segundaEspecialidad(): GradoFut
{
    return app(GuardarGradoFut::class)(null, ['nombre' => 'Segunda especialidad', 'titulo_otorgado' => 'Especialista', 'orden' => 3, 'activo' => true]);
}

it('ships master and doctorate with the degree each grants', function (): void {
    expect(GradoFut::query()->activos()->get(['codigo', 'nombre', 'titulo_otorgado'])->toArray())->toBe([
        ['codigo' => 'maestria', 'nombre' => 'Maestría', 'titulo_otorgado' => 'Maestro'],
        ['codigo' => 'doctorado', 'nombre' => 'Doctorado', 'titulo_otorgado' => 'Doctor'],
    ]);
});

it('accepts a new grade in the FUT and freezes its name and degree with the request', function (): void {
    $grado = segundaEspecialidad();

    $tramite = pedirMencion($this->persona, $grado->codigo);
    $grado->update(['nombre' => 'Segunda especialidad profesional']);

    expect($tramite->datos)->toEqual(['mencion_grado' => 'segunda_especialidad', 'mencion_grado_nombre' => 'Segunda especialidad', 'mencion_grado_titulo' => 'Especialista', 'mencion' => 'Gestión Pública'])
        ->and(PresentacionDelFut::valor(CampoFut::Mencion, $tramite->refresh()->datos))->toBe('Segunda especialidad en Gestión Pública');
});

it('refuses an inactive or unknown grade in the FUT', function (string $grado): void {
    segundaEspecialidad()->update(['activo' => false]);

    expect(fn (): Tramite => pedirMencion($this->persona, $grado))->toThrow(ReglaDeNegocioIncumplida::class, LecturaDelFut::MENCION_INVALIDA)
        ->and(Tramite::query()->count())->toBe(0);
})->with(['inactive' => 'segunda_especialidad', 'unknown' => 'bachiller']);

it('shows a request saved before the name was frozen with the name of the catalog', function (): void {
    expect(PresentacionDelFut::valor(CampoFut::Mencion, ['mencion_grado' => 'doctorado', 'mencion' => 'Educación']))->toBe('Doctorado en Educación');
});

it('prints the degree the catalog grants on the accreditation of a new grade', function (): void {
    segundaEspecialidad();
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '17.00', 'aprobado']]);
    $tramite = pedirMencion($this->persona, 'segunda_especialidad', AccionFinal::Acreditacion, ['idioma' => 'Inglés', 'nivel' => 'basico']);
    $personal = User::factory()->create()->givePermissionTo([PermisoTramite::Gestionar->value, PermisoCertificacion::Documentos->value]);
    app(AvanzarTramite::class)($tramite->id, $personal);

    app(FinalizarTramite::class)($tramite->id, $personal);

    $documento = Documento::query()->findOrFail($tramite->refresh()->documento_id);

    expect($documento->detalle['mencion'])->toBe('Segunda especialidad en Gestión Pública')
        ->and($documento->detalle['validez'])->toBe('Válido para obtener el Grado de Especialista en Gestión Pública.');
});

it('accredits the degree frozen with the request even when the grade is renamed before the record is issued', function (): void {
    $grado = segundaEspecialidad();
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '17.00', 'aprobado']]);
    $tramite = pedirMencion($this->persona, 'segunda_especialidad', AccionFinal::Acreditacion, ['idioma' => 'Inglés', 'nivel' => 'basico']);
    $grado->update(['nombre' => 'Segunda especialidad profesional', 'titulo_otorgado' => 'Especialista profesional']);
    $personal = User::factory()->create()->givePermissionTo([PermisoTramite::Gestionar->value, PermisoCertificacion::Documentos->value]);
    app(AvanzarTramite::class)($tramite->id, $personal);

    app(FinalizarTramite::class)($tramite->id, $personal);

    $documento = Documento::query()->findOrFail($tramite->refresh()->documento_id);

    expect($documento->detalle['mencion'])->toBe('Segunda especialidad en Gestión Pública')
        ->and($documento->detalle['validez'])->toBe('Válido para obtener el Grado de Especialista en Gestión Pública.');
});

it('reads a mention typed at the office by the longest grade name it starts with', function (): void {
    app(GuardarGradoFut::class)(null, ['nombre' => 'Doctorado honoris causa', 'titulo_otorgado' => 'Doctor Honoris Causa', 'orden' => 9, 'activo' => true]);
    ActasFinales::historial($this->persona, 'Inglés', [[1, '15.00', 'aprobado'], [2, '17.00', 'aprobado']]);
    $emisor = User::factory()->create()->givePermissionTo(PermisoCertificacion::Documentos->value);

    $documento = app(EmitirConstanciaDeAcreditacion::class)($this->persona->id, 'Inglés', NivelCurricular::Basico, 'Doctorado honoris causa en Educación', $emisor);

    expect($documento->detalle['validez'])->toBe('Válido para obtener el Grado de Doctor Honoris Causa en Educación.');
});

it('lets staff who configure procedures create and rename a grade whose code never changes, never delete it', function (): void {
    $this->actingAs($this->configurador);

    Livewire::test(CreateGradoFut::class)->fillForm(['nombre' => 'Segunda especialidad', 'titulo_otorgado' => 'Especialista', 'orden' => 3])->call('create')->assertHasNoFormErrors();

    $grado = GradoFut::query()->where('codigo', 'segunda_especialidad')->sole();

    Livewire::test(EditGradoFut::class, ['record' => $grado->getRouteKey()])->fillForm(['titulo_otorgado' => 'Especialista profesional', 'activo' => false])->call('save')->assertHasNoFormErrors();

    expect($grado->refresh())->titulo_otorgado->toBe('Especialista profesional')->codigo->toBe('segunda_especialidad')->activo->toBeFalse();

    Livewire::test(CreateGradoFut::class)->fillForm(['nombre' => 'Maestría', 'titulo_otorgado' => 'Maestro', 'orden' => 9])->call('create')->assertHasFormErrors(['nombre']);
    Livewire::test(ListGradosFut::class)->assertCanSeeTableRecords(GradoFut::query()->get())->assertTableActionDoesNotExist('delete');
});

it('keeps the grade screens away from staff who cannot configure procedures', function (): void {
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);

    $this->actingAs($gestor)->get(GradoFutResource::getUrl('index'))->assertForbidden();
    $this->actingAs($this->configurador)->get(GradoFutResource::getUrl('index'))->assertOk()->assertSeeText('Doctorado');
});

it('drops and recreates the catalog with its two shipped grades', function (): void {
    $migracion = require base_path('app/Modules/Tramites/Database/Migrations/2026_12_01_110300_create_tra_grados_fut_table.php');

    $migracion->down();

    expect(DB::getSchemaBuilder()->hasTable('tra_grados_fut'))->toBeFalse();

    $migracion->up();

    expect(GradoFut::query()->pluck('codigo')->all())->toBe(['maestria', 'doctorado']);
});
