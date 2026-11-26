<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Programa;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\CreateTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\EditTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\ListTiposTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\TiposTramite\TipoTramiteResource;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Repeater::fake();
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
});

/**
 * @return array<string, mixed>
 */
function formularioDeTipo(array $cambios = []): array
{
    return [
        'codigo' => 'duplicado-carne', 'nombre' => 'Duplicado de carné', 'requisitos' => 'El carné anterior o la denuncia de pérdida.',
        'tipo_concepto' => TipoConcepto::Tramite->value, 'accion_final' => AccionFinal::Ninguna->value, 'activo' => true,
        'etapas' => [
            ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => CondicionDeEtapa::Solicitud->value],
            ['nombre' => 'Pago validado', 'avance' => 40, 'condicion' => CondicionDeEtapa::PagoValidado->value],
            ['nombre' => 'Carné impreso', 'avance' => 100, 'condicion' => CondicionDeEtapa::AccionDelPersonal->value],
        ],
        ...$cambios,
    ];
}

it('keeps the trámite configuration away from staff without the configuration permission', function (): void {
    $personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);

    $this->actingAs($personal)->get(TipoTramiteResource::getUrl('index'))->assertForbidden();
    expect(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo()), $personal))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'No tienes permiso para configurar los trámites.');
});

it('lists the trámite types with their fee, final action and number of stages', function (): void {
    $this->actingAs($this->administrador);
    $tipo = TipoTramite::query()->where('codigo', 'RETIRO')->sole();

    Livewire::test(ListTiposTramite::class)
        ->assertCanSeeTableRecords([$tipo])
        ->assertTableColumnFormattedStateSet('tipo_concepto', 'Sin tasa', $tipo)
        ->assertTableColumnFormattedStateSet('accion_final', 'Retirar la matrícula', $tipo)
        ->assertTableColumnStateSet('etapas_count', 3, $tipo);
});

it('creates a trámite type with its stages in the given order', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateTipoTramite::class)->fillForm(formularioDeTipo())->call('create')->assertHasNoFormErrors();

    $tipo = TipoTramite::query()->with('etapas')->where('codigo', 'DUPLICADO-CARNE')->sole();
    expect($tipo)->tipo_concepto->toBe(TipoConcepto::Tramite)->accion_final->toBe(AccionFinal::Ninguna)
        ->and($tipo->etapas->map(fn ($etapa): string => "{$etapa->orden} {$etapa->nombre} {$etapa->avance}")->all())
        ->toBe(['1 Solicitud recibida 0', '2 Pago validado 40', '3 Carné impreso 100']);
});

it('refuses stages that break the pipeline rules or a repeated code, and saves nothing', function (array $cambios, string $mensaje): void {
    $this->actingAs($this->administrador);
    $antes = TipoTramite::query()->count();

    Livewire::test(CreateTipoTramite::class)->fillForm(formularioDeTipo($cambios))->call('create')->assertNotified($mensaje);

    expect(TipoTramite::query()->count())->toBe($antes);
})->with([
    'fee without its payment stage' => [['etapas' => [
        ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => 'solicitud'],
        ['nombre' => 'Entregado', 'avance' => 100, 'condicion' => 'accion_del_personal'],
    ]], 'Un trámite con tasa tiene una sola etapa de pago validado, justo después de la solicitud.'],
    'repeated code' => [['codigo' => 'retiro'], 'Ya existe un tipo de trámite con el código RETIRO.'],
]);

it('replaces the stages of a type for new requests while a trámite in progress keeps its own', function (): void {
    $this->actingAs($this->administrador);
    $tipo = TipoTramite::factory()->create(['codigo' => 'CONSTANCIA-PRUEBA']);
    $enCurso = Tramite::factory()->for($tipo)->create();

    Livewire::test(EditTipoTramite::class, ['record' => $tipo->getRouteKey()])
        ->assertFormSet(['codigo' => 'CONSTANCIA-PRUEBA', 'tipo_concepto' => 'constancia'])
        ->fillForm(['etapas' => [
            ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => 'solicitud'],
            ['nombre' => 'Pago validado', 'avance' => 50, 'condicion' => 'pago_validado'],
            ['nombre' => 'Entregado', 'avance' => 100, 'condicion' => 'accion_del_personal'],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tipo->etapas()->pluck('nombre')->all())->toBe(['Solicitud recibida', 'Pago validado', 'Entregado'])
        ->and(array_column($enCurso->refresh()->etapas, 'nombre'))->toBe(['Solicitud recibida', 'Pago validado', 'En firma de Dirección', 'Listo para recojo o descarga']);
});

it('creates a type without a fee and audits the stages it replaces', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateTipoTramite::class)->fillForm(formularioDeTipo(['tipo_concepto' => null, 'etapas' => [
        ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => 'solicitud'],
        ['nombre' => 'Atendido', 'avance' => 100, 'condicion' => 'accion_del_personal'],
    ]]))->call('create')->assertHasNoFormErrors();
    $tipo = TipoTramite::query()->where('codigo', 'DUPLICADO-CARNE')->sole();
    $etapaInicial = $tipo->etapas()->firstOrFail();

    app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo()), $this->administrador, $tipo->id);

    expect($tipo->refresh()->tipo_concepto)->toBe(TipoConcepto::Tramite)
        ->and(DB::table('aud_registros')->where(['tabla' => 'tra_etapas', 'registro_id' => $etapaInicial->id, 'operacion' => 'DELETE'])->exists())->toBeTrue();
});

it('refuses a fee of a concept that belongs to enrollment, monthly fees or the placement exam', function (TipoConcepto $concepto): void {
    expect(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['tipo_concepto' => $concepto->value])), $this->administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La tasa de un trámite es de constancia, certificado, trámite u otro concepto.');
})->with([TipoConcepto::Matricula, TipoConcepto::Pension, TipoConcepto::ExamenClasificacion]);

it('links a trámite type to a payment concept, taking the fee type from it', function (): void {
    $this->actingAs($this->administrador);
    $concepto = Concepto::factory()->create(['nombre' => 'Certificado de estudios', 'tipo' => TipoConcepto::Certificado]);

    Livewire::test(CreateTipoTramite::class)
        ->assertFormFieldExists('concepto_id')
        ->fillForm(formularioDeTipo(['concepto_id' => $concepto->id]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TipoTramite::query()->where('codigo', 'DUPLICADO-CARNE')->sole())
        ->concepto_id->toBe($concepto->id)->tipo_concepto->toBe(TipoConcepto::Certificado);
});

it('refuses a configuration that drops or relaxes a field the final action needs', function (array $campos): void {
    expect(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['accion_final' => AccionFinal::CertificadoNivel->value, ...$campos])), $this->administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La acción «Emitir el certificado de nivel» necesita el campo «Nivel» como obligatorio.')
        ->and(TipoTramite::query()->where('codigo', 'DUPLICADO-CARNE')->exists())->toBeFalse();
})->with([
    'dropped' => [['campos' => ['idioma'], 'obligatorios' => ['idioma']]],
    'optional' => [['campos' => ['idioma', 'nivel'], 'obligatorios' => ['idioma']]],
]);

it('configures the FUT fields, the previous type, the programs and the attachments of a type', function (): void {
    $this->actingAs($this->administrador);
    $posgrado = Programa::factory()->create(['nombre' => 'Posgrado de prueba']);
    $previo = TipoTramite::query()->where('codigo', 'CONSTANCIA-NOTAS')->sole();

    Livewire::test(CreateTipoTramite::class)
        ->fillForm(formularioDeTipo([
            'campos' => ['exposicion', 'celular', 'mencion'],
            'obligatorios' => ['exposicion', 'mencion'],
            'requiere_tipo_previo_id' => $previo->id,
            'programas' => [$posgrado->id],
            'adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true], ['nombre' => 'Fotografía', 'obligatorio' => false]],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $tipo = TipoTramite::query()->where('codigo', 'DUPLICADO-CARNE')->sole();
    expect($tipo->campos)->toBe([['clave' => 'mencion', 'obligatorio' => true], ['clave' => 'exposicion', 'obligatorio' => true], ['clave' => 'celular', 'obligatorio' => false]])
        ->and($tipo->requiere_tipo_previo_id)->toBe($previo->id)
        ->and($tipo->programas()->pluck('programa_id')->all())->toBe([$posgrado->id])
        ->and($tipo->adjuntos)->toBe([['nombre' => 'Copia de DNI', 'obligatorio' => true], ['nombre' => 'Fotografía', 'obligatorio' => false]]);

    Livewire::test(EditTipoTramite::class, ['record' => $tipo->getRouteKey()])
        ->assertFormSet(['campos' => ['mencion', 'exposicion', 'celular'], 'obligatorios' => ['mencion', 'exposicion'], 'requiere_tipo_previo_id' => $previo->id, 'programas' => [$posgrado->id]]);
});

it('refuses more than five attachments and a type that requires itself', function (): void {
    $seis = array_map(fn (int $numero): array => ['nombre' => "Documento {$numero}", 'obligatorio' => false], range(1, 6));
    $tipo = TipoTramite::query()->where('codigo', 'RETIRO')->sole();

    expect(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['adjuntos' => $seis])), $this->administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un trámite pide como máximo 5 adjuntos, cada uno con un nombre de 3 a 80 caracteres sin repetir.')
        ->and(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['codigo' => 'RETIRO', 'requiere_tipo_previo_id' => $tipo->id])), $this->administrador, $tipo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El trámite previo debe ser otro tipo de trámite.');
});

it('accepts as many attachments as the system parameters allow', function (): void {
    app(GuardarParametrosDelSistema::class)(['maximo_adjuntos_tramite' => 6], User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value));
    $documentos = fn (int $cantidad): array => array_map(fn (int $numero): array => ['nombre' => "Documento {$numero}", 'obligatorio' => false], range(1, $cantidad));

    $tipo = app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['adjuntos' => $documentos(6)])), $this->administrador);

    expect($tipo->refresh()->adjuntos)->toHaveCount(6)
        ->and(fn () => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(formularioDeTipo(['codigo' => 'OTRO', 'adjuntos' => $documentos(7)])), $this->administrador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un trámite pide como máximo 6 adjuntos, cada uno con un nombre de 3 a 80 caracteres sin repetir.');
});
