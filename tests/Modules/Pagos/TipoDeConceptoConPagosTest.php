<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\ActualizarConcepto;
use App\Modules\Pagos\Data\DatosDeConcepto;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\EditConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Configurar->value]);
    $this->concepto = Concepto::factory()->create(['nombre' => 'Examen de ubicación', 'tipo' => TipoConcepto::ExamenClasificacion]);
});

function datosDelConcepto(Concepto $concepto, array $cambios = []): DatosDeConcepto
{
    return new DatosDeConcepto(...[
        'nombre' => $concepto->nombre,
        'tipo' => $concepto->tipo,
        'fuenteId' => $concepto->fuente_id,
        'activo' => $concepto->activo,
        ...$cambios,
    ]);
}

it('keeps the service a concept enables once it has payments, while its other fields still change', function (): void {
    Pago::factory()->anulado()->create(['concepto_id' => $this->concepto->id]);

    expect(fn () => app(ActualizarConcepto::class)($this->concepto->id, datosDelConcepto($this->concepto, ['tipo' => TipoConcepto::Matricula])))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El concepto Examen de ubicación ya tiene pagos: el servicio que habilita no puede cambiar.')
        ->and($this->concepto->refresh()->tipo)->toBe(TipoConcepto::ExamenClasificacion);

    app(ActualizarConcepto::class)($this->concepto->id, datosDelConcepto($this->concepto, ['nombre' => 'Examen de clasificación', 'activo' => false]));

    expect($this->concepto->refresh())->nombre->toBe('Examen de clasificación')->activo->toBeFalse()->tipo->toBe(TipoConcepto::ExamenClasificacion)
        ->and(fn () => DB::table('pag_conceptos')->where('id', $this->concepto->id)->update(['tipo' => 'matricula']))
        ->toThrow(QueryException::class, 'un concepto con pagos no cambia el servicio que habilita');
});

it('still changes the service of a concept without payments', function (): void {
    app(ActualizarConcepto::class)($this->concepto->id, datosDelConcepto($this->concepto, ['tipo' => TipoConcepto::Otro]));

    expect($this->concepto->refresh()->tipo)->toBe(TipoConcepto::Otro);
});

it('locks the service field in the panel once the concept has payments and saves the rest', function (): void {
    $this->actingAs($this->administrador);
    $libre = Concepto::factory()->create(['tipo' => TipoConcepto::Constancia]);
    Pago::factory()->create(['concepto_id' => $this->concepto->id]);

    Livewire::test(EditConcepto::class, ['record' => $this->concepto->getRouteKey()])
        ->assertFormFieldDisabled('tipo')
        ->fillForm(['nombre' => 'Examen de clasificación'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->concepto->refresh())->nombre->toBe('Examen de clasificación')->tipo->toBe(TipoConcepto::ExamenClasificacion);

    Livewire::test(EditConcepto::class, ['record' => $libre->getRouteKey()])
        ->assertFormFieldEnabled('tipo')
        ->fillForm(['tipo' => TipoConcepto::Certificado->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($libre->refresh()->tipo)->toBe(TipoConcepto::Certificado);
});

it('reports a payment registered while the form was open instead of changing the service', function (): void {
    $this->actingAs($this->administrador);
    $pagina = Livewire::test(EditConcepto::class, ['record' => $this->concepto->getRouteKey()])
        ->assertFormFieldEnabled('tipo')
        ->fillForm(['tipo' => TipoConcepto::Matricula->value]);
    Pago::factory()->create(['concepto_id' => $this->concepto->id]);

    $pagina->call('save')->assertNotified('El concepto Examen de ubicación ya tiene pagos: el servicio que habilita no puede cambiar.');

    expect($this->concepto->refresh()->tipo)->toBe(TipoConcepto::ExamenClasificacion);
});
