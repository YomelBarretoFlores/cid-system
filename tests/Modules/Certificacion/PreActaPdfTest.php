<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ViewGrupo;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Docencia\Filament\Docente\Pages\PreActaDelGrupo;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    ActasFinales::preActa(ActasFinales::estudiante($this->grupo, 'Alva', '70000001'), [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00']);
});

it('gives the teacher the pre-acta as the evaluation register: course data, short column names with their legend and signature boxes', function (): void {
    Pdf::fake();
    $this->grupo->horarios()->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '09:30']);

    $this->actingAs($this->curso['usuario'])->get(route('docente.preacta.pdf', $this->grupo->id))->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        $html = view($pdf->viewName, $pdf->viewData)->render();
        $esperado = [
            'REGISTRO DE EVALUACIÓN', 'Marzo 2027', 'VICERRECTORADO ACADÉMICO',
            'VEGA RÍOS, CARLOS', 'Número de alumnos', '30 mar 2027',
            $this->grupo->programa->nombre, 'Currícula', 'Básico', 'I', 'ING-1-M', '08:00',
            'ALVA PRUEBA, ANA', '15', '12',
            '>EO<', '>EP<', '>EF<', '>PH<', '>PE<', '>NF<',
            'SISTEMA DE CALIFICACIÓN', 'EO = Expresión oral', 'PH = Promedio de habilidades', 'NF = Nota final',
            'Vº Bº DIRECTOR(A) ACADÉMICO', 'FIRMA DEL DOCENTE', 'Documento de trabajo',
        ];

        return $pdf->downloadName === 'pre-acta-ING-1-M.pdf' && $pdf->orientation === 'Landscape'
            && collect($esperado)->every(fn (string $texto): bool => str_contains($html, $texto))
            && ! str_contains($html, 'SIN VALOR OFICIAL');
    });
});

it('keeps the pre-acta PDF away from another teacher and from students', function (): void {
    [$otro] = PortalDocente::docente();

    $this->get(route('docente.preacta.pdf', $this->grupo->id))->assertRedirect();
    $this->actingAs($otro)->get(route('docente.preacta.pdf', $this->grupo->id))->assertNotFound();
});

it('gives coordination the pre-acta PDF from the panel, only with the actas permission', function (): void {
    Pdf::fake();
    $coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    $this->actingAs($sinPermiso)->get(route('panel.grupos.preacta.pdf', $this->grupo->id))->assertForbidden();
    $this->actingAs($coordinador)->get(route('panel.grupos.preacta.pdf', $this->grupo->id))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'pre-acta-ING-1-M.pdf');
});

it('sends to the acta final once the acta is closed', function (): void {
    app(CerrarActa::class)($this->grupo->id, $this->curso['docente']->id, $this->curso['usuario']);

    $this->actingAs($this->curso['usuario'])->get(route('docente.preacta.pdf', $this->grupo->id))->assertNotFound();
});

it('offers the pre-acta download on the teacher grades page and on the group page for coordination', function (): void {
    Filament::setCurrentPanel('docente');
    Livewire::actingAs($this->curso['usuario'])
        ->test(PreActaDelGrupo::class, ['grupo' => (string) $this->grupo->id])
        ->assertActionHasUrl('descargarPreActa', route('docente.preacta.pdf', $this->grupo->id));

    Filament::setCurrentPanel('admin');
    $coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value, PermisoCertificacion::Actas->value]);
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);

    Livewire::actingAs($coordinador)->test(ViewGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->assertActionVisible('descargarPreActa')
        ->assertActionHasUrl('descargarPreActa', route('panel.grupos.preacta.pdf', $this->grupo->id));
    Livewire::actingAs($lector)->test(ViewGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->assertActionHidden('descargarPreActa');
});
