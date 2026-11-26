<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\GuardarDatosInstitucionales;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Institucion;
use App\Modules\Personas\Actions\GuardarTextosLegales;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Pages\EditarPoliticaDePrivacidad;
use App\Modules\Personas\Models\TextosLegales;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-12-02 10:00', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Gestionar->value]);
});

/**
 * @return array<string, string>
 */
function textosLegalesEditados(array $cambios = []): array
{
    return [
        'politica_privacidad' => "Política sintética de {centro}.\n\n## Titular\n\n{universidad}, con domicilio en {domicilio}.",
        'domicilio_titular' => 'Av. Sintética 123, Ciudad Prueba',
        'consentimiento_registro' => 'Acepto el tratamiento sintético según la {politica}.',
        ...$cambios,
    ];
}

it('shows today\'s policy, consent and date until staff edit them', function (): void {
    $this->get(route('privacidad'))
        ->assertOk()
        ->assertSee('Universidad Nacional Santiago Antúnez de Mayolo · Centro de Idiomas y Lenguas Nativas')
        ->assertSee('Universidad Nacional Santiago Antúnez de Mayolo – Centro de Idiomas y Lenguas Nativas (CILN), con domicilio en Av. Centenario N.° 200, Independencia, Huaraz, Áncash.')
        ->assertSee('<table class="tabla-formal">', false)
        ->assertSee('Última actualización: 19 de noviembre de 2026');

    $this->get(route('estudiante.registro'))
        ->assertSee('Acepto que el CILN de la UNASAM trate mis datos personales para gestionar mi matrícula, pagos y certificados, conforme a la Ley N° 29733, Ley de Protección de Datos Personales, y según su <a href="'.route('privacidad').'" target="_blank" rel="noopener" class="enlace">política de privacidad</a>.', false);

    expect(TextosLegales::query()->count())->toBe(0);
});

it('publishes the edited policy and consent from the panel page and dates the policy', function (): void {
    Livewire::actingAs($this->encargado)->test(EditarPoliticaDePrivacidad::class)
        ->assertSet('data.domicilio_titular', 'Av. Centenario N.° 200, Independencia, Huaraz, Áncash')
        ->fillForm(textosLegalesEditados())
        ->call('guardar')
        ->assertHasNoFormErrors();

    $this->get(route('privacidad'))
        ->assertSee('Política sintética de Centro de Idiomas y Lenguas Nativas.')
        ->assertSee('<h2>Titular</h2>', false)
        ->assertSee('Universidad Nacional Santiago Antúnez de Mayolo, con domicilio en Av. Sintética 123, Ciudad Prueba.')
        ->assertDontSee('Av. Centenario')
        ->assertSee('Última actualización: 2 de diciembre de 2026');
    auth()->logout();
    $this->get(route('estudiante.registro'))
        ->assertSee('Acepto el tratamiento sintético según la <a href="'.route('privacidad').'"', false);

    expect(DB::table('aud_registros')->where(['tabla' => 'per_textos_legales', 'operacion' => 'INSERT'])->count())->toBe(1);
});

it('keeps the policy date when only the consent sentence changes', function (): void {
    app(GuardarTextosLegales::class)(textosLegalesEditados(), $this->encargado);
    $this->travelTo(CarbonImmutable::parse('2026-12-20 10:00', 'America/Lima'));

    app(GuardarTextosLegales::class)(textosLegalesEditados(['consentimiento_registro' => 'Otra frase con la {politica}.']), $this->encargado);

    expect(TextosLegales::query()->sole()->politica_actualizada_el->toDateString())->toBe('2026-12-02');
});

it('takes the university and centre of the policy from the institutional data', function (): void {
    $admin = User::factory()->create()->givePermissionTo([PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
    app(GuardarDatosInstitucionales::class)([
        ...Institucion::query()->findOrFail(Institucion::FILA)->only([
            'lema', 'direccion', 'telefono', 'web', 'facebook', 'ciudad', 'formato_numero', 'formato_numero_constancia_notas', 'resolucion_creacion', 'base_legal_acreditacion',
        ]),
        'universidad' => 'Universidad Sintética', 'centro' => 'Centro Sintético',
    ], $admin);

    $this->get(route('privacidad'))->assertSee('Universidad Sintética · Centro Sintético')->assertSee('Universidad Sintética – Centro Sintético (CILN)');
});

it('shows HTML typed in the legal texts as text and drops unsafe links', function (): void {
    app(GuardarTextosLegales::class)(textosLegalesEditados([
        'politica_privacidad' => "<script>alert('x')</script>\n\n[enlace](javascript:alert(1)) <img src=x onerror=alert(1)>",
        'consentimiento_registro' => '<b>Acepto</b> la {politica}.',
    ]), $this->encargado);

    $this->get(route('privacidad'))
        ->assertDontSee('<script>alert', false)
        ->assertDontSee('<img src=x', false)
        ->assertDontSee('javascript:alert', false)
        ->assertSee('&lt;script&gt;', false);
    $this->get(route('estudiante.registro'))
        ->assertSee('&lt;b&gt;Acepto&lt;/b&gt; la <a href="', false);
});

it('refuses blank texts and a consent without the policy link', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarTextosLegales::class)(textosLegalesEditados($cambios), $this->encargado))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(TextosLegales::query()->count())->toBe(0);
})->with([
    'blank policy' => [['politica_privacidad' => '  '], 'Completa la política de privacidad, hasta 20000 caracteres.'],
    'consent without link' => [['consentimiento_registro' => 'Acepto.'], 'La frase de consentimiento debe incluir {politica}, donde va el enlace a la política de privacidad.'],
]);

it('lets only staff who manage people edit the legal texts', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]);

    expect(fn () => app(GuardarTextosLegales::class)(textosLegalesEditados(), $otro))->toThrow(AuthorizationException::class);
    $this->actingAs($otro)->get(EditarPoliticaDePrivacidad::getUrl())->assertForbidden();
    $this->actingAs($this->encargado)->get(EditarPoliticaDePrivacidad::getUrl())->assertOk();
});
