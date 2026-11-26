<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirCertificadoDeNivel;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeAcreditacion;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Actions\GuardarTextosDeDocumentos;
use App\Modules\Certificacion\Contracts\TextosDeDocumentos;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Pages\EditarTextosDeDocumentos;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\EmisionDeDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value]);
    $this->ana = Persona::factory()->create(['numero_documento' => '70000001']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '17.00', 'aprobado'], [2, '18.00', 'aprobado'], [3, '18.50', 'aprobado'], [4, '17.50', 'aprobado']]);
    $this->html = function (Documento $documento): string {
        $pdf = app(GenerarDocumentoPdf::class)($documento->id);

        return view($pdf->viewName, [...$pdf->viewData, 'qr' => CodigoQr::comoDataUri('x')])->render();
    };
    $this->emitir = fn (): array => [
        'matricula' => app(EmisionDeDocumento::class)->emitir(TipoDocumento::ConstanciaMatricula, $this->ana->id, [
            'idioma' => 'Inglés', 'detalle' => ['grupo' => 'ING-1A', 'ciclo_numero' => 1, 'periodo' => '2026-II', 'modalidad' => 'Presencial', 'horario' => [], 'matriculado_el' => '2026-11-01'],
        ], $this->encargado),
        'notas' => app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->encargado),
        'certificado' => app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->encargado),
        'acreditacion' => app(EmitirConstanciaDeAcreditacion::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, 'Doctorado en Administración', $this->encargado),
    ];
});

/**
 * @return array<string, string>
 */
function textosDeDocumentosEditados(array $cambios = []): array
{
    return [
        'cierre_constancia_matricula' => 'Cierre sintético de matrícula.', 'cierre_constancia_notas' => 'Cierre sintético de notas.',
        'escala_notas' => 'Escala sintética.', 'programa_acreditacion' => 'Programa sintético', 'suficiencia_acreditacion' => 'Suficiencia sintética',
        'constancia_acta_acreditacion' => 'Consta en el acta sintética.', 'validez_acreditacion' => 'Sirve para el grado de {mencion}.',
        'texto_certificado' => 'Aprobó el nivel {nivel} de {idioma}, con {horas}.',
        ...$cambios,
    ];
}

it('prints today\'s wording and institutional names by default', function (): void {
    ['matricula' => $matricula, 'notas' => $notas, 'certificado' => $certificado, 'acreditacion' => $acreditacion] = ($this->emitir)();

    expect(($this->html)($matricula))->toContain('LA DIRECCIÓN DEL CENTRO DE IDIOMAS Y LENGUAS NATIVAS DE LA UNIVERSIDAD NACIONAL SANTIAGO ANTÚNEZ DE MAYOLO')
        ->toContain('Se expide la presente a solicitud de la persona interesada, para los fines que estime conveniente.')
        ->and(($this->html)($notas))->toContain('Escala vigesimal de 0 a 20.')
        ->and(($this->html)($certificado))->toContain('Por haber aprobado satisfactoriamente el nivel <strong>BÁSICO</strong> del idioma <strong>INGLÉS</strong> desarrollado en el Centro de Idiomas de la UNASAM; haciendo un total de <strong>')
        ->and(($this->html)($acreditacion))->toContain('Así consta en el Acta Final correspondiente, disponible para verificación en caso de estimarse necesario.')
        ->toContain('Programa de Enseñanza de Idioma Extranjero')
        ->toContain('Válido para obtener el Grado de Doctor en Administración.')
        ->and($certificado->detalle['textos'])->toBe(['certificado' => app(TextosDeDocumentos::class)()->textoCertificado]);
});

it('saves the texts from the panel page and new documents print them', function (): void {
    Livewire::actingAs($this->encargado)->test(EditarTextosDeDocumentos::class)
        ->assertSet('data.escala_notas', 'Escala vigesimal de 0 a 20.')
        ->fillForm(textosDeDocumentosEditados())
        ->call('guardar')
        ->assertHasNoFormErrors();

    ['matricula' => $matricula, 'notas' => $notas, 'certificado' => $certificado, 'acreditacion' => $acreditacion] = ($this->emitir)();

    expect(($this->html)($matricula))->toContain('Cierre sintético de matrícula.')->not->toContain('Se expide la presente')
        ->and(($this->html)($notas))->toContain('Escala sintética.')
        ->and(($this->html)($certificado))->toContain('Aprobó el nivel <strong>BÁSICO</strong> de <strong>INGLÉS</strong>, con <strong>')
        ->and(($this->html)($acreditacion))->toContain('Consta en el acta sintética.')->toContain('Programa sintético')->toContain('Sirve para el grado de Doctor en Administración.')
        ->and(DB::table('aud_registros')->where(['tabla' => 'cer_textos_documentos', 'operacion' => 'UPDATE'])->count())->toBe(1);
});

it('keeps the texts an issued document printed when they are edited later', function (): void {
    $documentos = ($this->emitir)();
    $antes = array_map($this->html, $documentos);

    app(GuardarTextosDeDocumentos::class)(textosDeDocumentosEditados(), $this->encargado);

    foreach ($documentos as $clave => $documento) {
        expect(($this->html)($documento->fresh()))->toBe($antes[$clave]);
    }
});

it('prints the original wording on documents issued before the texts were editable', function (): void {
    app(GuardarTextosDeDocumentos::class)(textosDeDocumentosEditados(), $this->encargado);
    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->encargado);
    // An issued document is immutable; the guard is lifted only to rebuild one issued before this change.
    DB::statement('ALTER TABLE cer_documentos DISABLE TRIGGER cer_documentos_bloqueo');
    DB::table('cer_documentos')->where('id', $documento->id)->update(['detalle' => DB::raw("detalle::jsonb - 'textos'")]);
    DB::statement('ALTER TABLE cer_documentos ENABLE TRIGGER cer_documentos_bloqueo');

    expect(($this->html)($documento->fresh()))->toContain('Escala vigesimal de 0 a 20.')->not->toContain('Escala sintética.');
});

it('escapes markup typed in a text instead of printing it as HTML', function (): void {
    app(GuardarTextosDeDocumentos::class)(textosDeDocumentosEditados(['texto_certificado' => '<script>alert(1)</script> nivel {nivel} de {idioma}']), $this->encargado);

    $html = ($this->html)(app(EmitirCertificadoDeNivel::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->encargado));

    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt; nivel <strong>BÁSICO</strong>')->not->toContain('<script>alert(1)');
});

it('refuses blank texts and templates without their markers', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarTextosDeDocumentos::class)(textosDeDocumentosEditados($cambios), $this->encargado))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(app(TextosDeDocumentos::class)()->escalaNotas)->toBe('Escala vigesimal de 0 a 20.');
})->with([
    'blank closing' => [['cierre_constancia_notas' => ' '], 'Completa la frase final de la constancia de notas, hasta 300 caracteres.'],
    'validity without mention' => [['validez_acreditacion' => 'Válido.'], 'La frase de validez de la acreditación debe incluir {mencion}.'],
    'certificate without level' => [['texto_certificado' => 'Aprobó {idioma}.'], 'El texto del certificado debe incluir {nivel}.'],
]);

it('lets only staff who issue documents edit the texts', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);

    expect(fn () => app(GuardarTextosDeDocumentos::class)(textosDeDocumentosEditados(), $otro))->toThrow(AuthorizationException::class);
    $this->actingAs($otro)->get(EditarTextosDeDocumentos::getUrl())->assertForbidden();
    $this->actingAs($this->encargado)->get(EditarTextosDeDocumentos::getUrl())->assertOk();
});
