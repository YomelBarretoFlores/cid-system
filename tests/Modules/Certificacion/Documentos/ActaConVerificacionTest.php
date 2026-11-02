<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Acta;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    ActasFinales::preActa(ActasFinales::estudiante($this->curso['grupo'], 'Quispe', '70000001'), [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '14.00', $this->curso['participacion']->id => '16.00']);
    ActasFinales::preActa(ActasFinales::estudiante($this->curso['grupo'], 'Rojas', '70000002'), [$this->curso['parcial']->id => '8.00', $this->curso['final']->id => '9.00']);
    ActasFinales::estudiante($this->curso['grupo'], 'Soto', '70000003');
    app(CerrarActa::class)($this->curso['grupo']->id, $this->curso['docente']->id, $this->curso['usuario']);
    $this->acta = Acta::query()->sole();
});

it('prints the acta with the grades in words, the totals, the closing box and a QR to its verification', function (): void {
    $pdf = app(GenerarActaFinalPdf::class)($this->acta->id);
    $html = view($pdf->viewName, $pdf->viewData)->render();

    expect($html)->toContain('ACTA FINAL DE EVALUACIÓN', 'CERRADA · 30/03/2027', 'En letras', 'Catorce con ochenta', 'Matriculados', 'Aprobados', 'Desaprobados', 'NSP', 'Sello', 'data:image/png;base64,')
        ->and($pdf->viewData['verificacion'])->toContain('/verificar/acta/'.$this->acta->id)->toContain('signature=');
});

it('verifies an acta from its signed link showing only its group, period, state and closing date', function (): void {
    $pdf = app(GenerarActaFinalPdf::class)($this->acta->id);

    $this->get($pdf->viewData['verificacion'])
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSeeInOrder(['Acta final auténtica y cerrada', 'ING-1-M', 'Inglés', 'Marzo 2027', 'Cerrada', '30 mar 2027', '3 estudiantes'])
        ->assertDontSee(['Quispe', 'Rojas', '70000001', '14.80', 'Vega Ríos']);
});

it('refuses an acta link without its signature or with another acta number', function (): void {
    $firmada = URL::signedRoute('verificacion.acta', ['acta' => $this->acta->id]);

    $this->get(route('verificacion.acta', $this->acta->id))->assertForbidden();
    $this->get(str_replace('/acta/'.$this->acta->id, '/acta/'.($this->acta->id + 1), $firmada))->assertForbidden();
    $this->get(substr($firmada, 0, -2).'00')->assertForbidden();
    $this->get($firmada.'&otro=1')->assertForbidden();
});

it('keeps the printed QR valid when the request comes from another host, since only the path is signed', function (): void {
    $enlace = app(GenerarActaFinalPdf::class)($this->acta->id)->viewData['verificacion'];

    expect($enlace)->toStartWith(rtrim((string) config('app.url'), '/').'/verificar/acta/');
    $this->get(str_replace(rtrim((string) config('app.url'), '/'), 'http://10.0.0.5:8080', $enlace))->assertOk();
});

it('warns that an acta in rectification may not match its printed copy', function (): void {
    $enlace = app(GenerarActaFinalPdf::class)($this->acta->id)->viewData['verificacion'];
    app(AutorizarRectificacion::class)($this->acta->id, 'Nota mal registrada', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));

    $this->get($enlace)->assertOk()->assertSee(['Acta en rectificación', 'la versión impresa puede no ser la vigente'])->assertDontSee('Acta final auténtica y cerrada');
});
