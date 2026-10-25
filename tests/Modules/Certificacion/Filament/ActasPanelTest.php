<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Filament\Resources\Actas\ActaResource;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ListActas;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ViewActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->gestor = User::factory()->create(['name' => 'Coordinación'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);
    $this->curso = ActasFinales::curso();
    $ana = ActasFinales::estudiante($this->curso['grupo'], 'Alva', '70000001');
    $beto = ActasFinales::estudiante($this->curso['grupo'], 'Bravo', '70000002');
    ActasFinales::preActa($ana, [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00', $this->curso['participacion']->id => '18.00']);
    ActasFinales::preActa($beto, [$this->curso['parcial']->id => '8.00']);
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);
    $this->acta = Acta::query()->sole();
});

it('keeps the actas away from panel users without the actas permission', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($ajeno)->get(ActaResource::getUrl('index'))->assertForbidden();
    $this->actingAs($ajeno)->get(ActaResource::getUrl('view', ['record' => $this->acta]))->assertForbidden();
    $this->actingAs($ajeno)->get(route('panel.actas.pdf', $this->acta))->assertForbidden();
});

it('lists the actas with course, teacher, passed students and state, without creating or editing them', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListActas::class)
        ->assertCanSeeTableRecords([$this->acta])
        ->assertTableColumnStateSet('curso', 'Inglés · Básico I', $this->acta)
        ->assertTableColumnStateSet('docente_nombre', 'Vega Ríos, Carlos', $this->acta)
        ->assertTableColumnStateSet('aprobados', '1 de 2', $this->acta)
        ->assertTableColumnFormattedStateSet('estado', 'Cerrada', $this->acta)
        ->assertActionDoesNotExist('create');

    expect(ActaResource::canCreate())->toBeFalse()
        ->and(ActaResource::canEdit($this->acta))->toBeFalse()
        ->and(ActaResource::canDelete($this->acta))->toBeFalse();
});

it('filters the actas by state and by period', function (): void {
    $otroCurso = Acta::query()->create([...$this->acta->only(['estado', 'idioma', 'nivel', 'ciclo_numero', 'ciclo_nombre', 'horas_lectivas', 'docente_id', 'docente_nombre', 'esquema_id', 'esquema_nombre', 'nota_maxima', 'nota_minima_aprobatoria', 'componentes', 'validada_por', 'validada_en']),
        'grupo_id' => Grupo::factory()->create()->id, 'periodo_id' => Periodo::factory()->create()->id, 'grupo_codigo' => 'FRA-1-N', 'periodo' => 'Otro', 'estado' => EstadoActa::Abierta]);
    $this->actingAs($this->gestor);

    Livewire::test(ListActas::class)
        ->filterTable('estado', EstadoActa::Abierta->value)
        ->assertCanSeeTableRecords([$otroCurso])
        ->assertCanNotSeeTableRecords([$this->acta])
        ->resetTableFilters()
        ->filterTable('periodo_id', $this->acta->periodo_id)
        ->assertCanSeeTableRecords([$this->acta])
        ->assertCanNotSeeTableRecords([$otroCurso]);
});

it('shows the acta with its grades, averages and final states', function (): void {
    $this->actingAs($this->gestor)
        ->get(ActaResource::getUrl('view', ['record' => $this->acta]))
        ->assertOk()
        ->assertSeeInOrder(['Inglés', 'Básico · Básico I', 'Marzo 2027', 'ING-1-M', 'Vega Ríos, Carlos', 'Cerrada',
            'Examen parcial (40 %)', 'DNI 70000001', 'Alva Prueba, Ana', '15', '12', '18', '14.40', 'Aprobado',
            'DNI 70000002', 'Bravo Prueba, Ana', '8', '—', '3.20', 'Desaprobado', 'Sin rectificaciones.']);
});

it('authorizes a rectification with its reason and records who did it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ViewActa::class, ['record' => $this->acta->getRouteKey()])
        ->callAction('autorizarRectificacion', ['motivo' => 'Error al transcribir el examen final.'])
        ->assertHasNoActionErrors()
        ->assertNotified('Rectificación autorizada')
        ->assertActionHidden('autorizarRectificacion')
        ->assertActionHidden('descargarPdf');

    expect($this->acta->refresh()->estado)->toBe(EstadoActa::Abierta)
        ->and(Rectificacion::query()->sole())
        ->motivo->toBe('Error al transcribir el examen final.')
        ->autorizada_por->toBe($this->gestor->id);

    $this->get(ActaResource::getUrl('view', ['record' => $this->acta]))
        ->assertSeeInOrder(['En rectificación', 'Error al transcribir el examen final.', 'Coordinación', 'Pendiente de validación']);
});

it('requires a reason of 5 to 500 characters to authorize a rectification', function (string $motivo, string $regla): void {
    $this->actingAs($this->gestor);

    Livewire::test(ViewActa::class, ['record' => $this->acta->getRouteKey()])
        ->callAction('autorizarRectificacion', ['motivo' => $motivo])
        ->assertHasActionErrors(['motivo' => $regla]);

    expect($this->acta->refresh()->estado)->toBe(EstadoActa::Cerrada);
})->with([
    'empty' => ['', 'required'],
    'too short' => ['Mal', 'min'],
    'too long' => [str_repeat('a', 501), 'max'],
]);

it('downloads the PDF of a closed acta from the panel and refuses it while under rectification', function (): void {
    Pdf::fake();
    $this->actingAs($this->gestor)->get(route('panel.actas.pdf', $this->acta))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->viewName === 'certificacion::pdf.acta-final');

    $this->acta->update(['estado' => EstadoActa::Abierta]);

    $this->actingAs($this->gestor)->get(route('panel.actas.pdf', $this->acta))->assertNotFound();
});
