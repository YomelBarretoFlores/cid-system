<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AvanzarTramite;
use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\CreateTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\EditTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    Repeater::fake();
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
});

/**
 * @return array<string, mixed>
 */
function tipoDeExamen(array $cambios = []): array
{
    return [
        'codigo' => 'examen-prueba', 'nombre' => 'Examen de prueba', 'requisitos' => 'FUT digital.',
        'tipo_concepto' => TipoConcepto::Otro->value, 'activo' => true,
        'etapas' => [
            ['nombre' => 'Solicitud recibida', 'avance' => 0, 'condicion' => CondicionDeEtapa::Solicitud->value],
            ['nombre' => 'Pago validado', 'avance' => 50, 'condicion' => CondicionDeEtapa::PagoValidado->value],
            ['nombre' => 'Resultado registrado', 'avance' => 100, 'condicion' => CondicionDeEtapa::AccionDelPersonal->value],
        ],
        ...$cambios,
    ];
}

it('ticks the fields the chosen final action needs when creating a type, so saving without touching them works', function (): void {
    $this->actingAs($this->administrador);

    Livewire::test(CreateTipoTramite::class)
        ->fillForm(['accion_final' => AccionFinal::Acreditacion->value])
        ->assertFormSet(['campos' => ['idioma', 'nivel', 'mencion'], 'obligatorios' => ['idioma', 'nivel', 'mencion']])
        ->fillForm(['accion_final' => AccionFinal::Suficiencia->value])
        ->assertFormSet(['campos' => ['idioma', 'nivel'], 'obligatorios' => ['idioma', 'nivel']])
        ->fillForm(tipoDeExamen())
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TipoTramite::query()->where('codigo', 'EXAMEN-PRUEBA')->sole()->campos)->toBe([['clave' => 'idioma', 'obligatorio' => true], ['clave' => 'nivel', 'obligatorio' => true]]);
});

it('leaves the fields of an existing type alone when its final action changes', function (): void {
    $this->actingAs($this->administrador);
    $tipo = TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Ninguna, 'campos' => [['clave' => 'exposicion', 'obligatorio' => true]]]);

    Livewire::test(EditTipoTramite::class, ['record' => $tipo->getRouteKey()])
        ->fillForm(['accion_final' => AccionFinal::Resolucion->value])
        ->assertFormSet(['campos' => ['exposicion'], 'obligatorios' => ['exposicion']]);
});

it('keeps the months without studying only for a reclassification type, between 1 and 24', function (): void {
    $guardar = fn (array $cambios): TipoTramite => app(GuardarTipoDeTramite::class)(TipoTramiteForm::datos(tipoDeExamen($cambios)), $this->administrador);

    expect($guardar(['accion_final' => AccionFinal::Reclasificacion->value, 'campos' => ['idioma'], 'obligatorios' => ['idioma'], 'meses_sin_estudiar' => '4'])->meses_sin_estudiar)->toBe(4)
        ->and($guardar(['codigo' => 'otro-examen', 'accion_final' => AccionFinal::Suficiencia->value, 'campos' => ['idioma', 'nivel'], 'obligatorios' => ['idioma', 'nivel'], 'meses_sin_estudiar' => '4'])->meses_sin_estudiar)->toBeNull()
        ->and(fn (): TipoTramite => $guardar(['codigo' => 'tercero', 'accion_final' => AccionFinal::Reclasificacion->value, 'campos' => ['idioma'], 'obligatorios' => ['idioma'], 'meses_sin_estudiar' => '25']))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Los meses sin estudiar para la reclasificación van de 1 a 24.');
});

it('asks the staff answer of a resolution and the cycle of a reclassification when finishing from the panel', function (): void {
    $personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);
    $persona = Persona::factory()->create();
    $this->actingAs($personal);
    $resolucion = app(SolicitarTramite::class)($persona->id, TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Resolucion])->id, new DatosDeSolicitud);
    app(AvanzarTramite::class)($resolucion->id, $personal);

    Livewire::test(ViewTramite::class, ['record' => $resolucion->getRouteKey()])
        ->callAction('finalizar', ['comentario' => ''])
        ->assertHasActionErrors(['comentario' => 'required']);

    Livewire::test(ViewTramite::class, ['record' => $resolucion->getRouteKey()])
        ->callAction('finalizar', ['comentario' => 'Se atendió su pedido en ventanilla.'])
        ->assertNotified('Trámite finalizado')
        ->assertSeeText('Respuesta del CILN: Se atendió su pedido en ventanilla.');

    $this->travelTo(CarbonImmutable::parse('2027-06-01 10:00', 'America/Lima'));
    ActasFinales::historial($persona, 'Inglés', [[1, '15.00', 'aprobado']]);
    $ciclo = Ciclo::query()->where(['idioma_id' => ActasFinales::planDeEstudios('Inglés')->id, 'numero' => 3])->sole();
    $reclasificacion = app(SolicitarTramite::class)($persona->id, TipoTramite::factory()->sinTasa()->create(['accion_final' => AccionFinal::Reclasificacion])->id, new DatosDeSolicitud(idioma: 'Inglés'));
    app(AvanzarTramite::class)($reclasificacion->id, $personal);

    Livewire::test(ViewTramite::class, ['record' => $reclasificacion->getRouteKey()])
        ->callAction('finalizar', ['ciclo_id' => $ciclo->id])
        ->assertNotified('Trámite finalizado')
        ->assertSeeText('Ciclo asignado: Ciclo 3 (Básico)');
});
