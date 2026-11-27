<?php

declare(strict_types=1);

use App\Modules\Tramites\Actions\ConsultarTextosDePortada;
use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Pages\EditarContenidoDePortada;
use App\Modules\Tramites\Filament\Pages\Schemas\FormularioDeContenidoDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
    Storage::fake('public');
});

/**
 * @return array<string, mixed>
 */
function portadaConCambios(array $cambios = []): array
{
    return [...FormularioDeContenidoDePortada::estado(app(ConsultarTextosDePortada::class)()), ...$cambios];
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, array<string, mixed>>
 */
function seccionCambiada(string $clave, array $cambios): array
{
    return [$clave => [...TextosDeLaPortada::completas()[$clave], ...$cambios]];
}

it('shows today\'s cover, section titles and footer by default, with the address and phone of the institutional data', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('src="'.asset('images/portada/portada.jpg').'"', false)
        ->assertSeeText('para estudiantes universitarios, profesionales y público en general.')
        ->assertSeeTextInOrder(['Ver horarios', 'Crear cuenta'])
        ->assertSeeTextInOrder(['El centro', 'Idiomas para la universidad y la región'])
        ->assertSeeTextInOrder(['Oferta académica', 'Cada idioma se estudia por ciclos, organizados en niveles.'])
        ->assertSeeText('Todo el proceso es en línea: cree su cuenta, registre su pago y elija un grupo con vacantes.')
        ->assertSeeTextInOrder(['TUPA / TUSNE', 'Costos según su vínculo con la UNASAM', 'Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.'])
        ->assertSeeText('Quien ya conoce el idioma puede rendir el examen de clasificación.')
        ->assertSeeTextInOrder(['Huaraz, Áncash', 'Matrícula en línea'])
        ->assertSeeText('Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos por nivel.')
        ->assertSeeTextInOrder(['Dirección:', 'Prolongación Simón Bolívar S/N – Huaraz', 'Teléfono:', '043-640020 / Anexo 3105'])
        ->assertDontSeeText('Correo:')
        ->assertDontSee('aria-label="Redes del centro"', false);
});

it('publishes the cover texts, section titles and footer saved in the panel', function (): void {
    Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->fillForm([
            'subtitulo' => 'para toda la comunidad sintética.',
            'boton_principal' => 'Horarios sintéticos',
            'boton_secundario' => 'Registro sintético',
            'secciones' => [
                ...TextosDeLaPortada::completas(),
                ...seccionCambiada('costos', ['etiqueta' => 'Tarifas sintéticas', 'titulo' => 'Costos sintéticos', 'introduccion' => 'Pague en {lugares_de_pago}.']),
                ...seccionCambiada('oferta', ['etiqueta' => 'Grupos sintéticos', 'titulo' => 'Vacantes sintéticas']),
            ],
            'pie' => 'Descripción sintética del pie.',
            'correo' => 'contacto@example.test',
            'horario_atencion' => 'Lunes a viernes, 8:00 a 16:00',
            'redes' => [['etiqueta' => 'Facebook', 'url' => 'https://www.facebook.com/example']],
        ])
        ->call('guardar')
        ->assertHasNoFormErrors();

    $this->get('/')
        ->assertSeeText('para toda la comunidad sintética.')
        ->assertSeeTextInOrder(['Horarios sintéticos', 'Registro sintético'])
        ->assertSeeTextInOrder(['Tarifas sintéticas', 'Costos sintéticos', 'Pague en Caja presencial, Tesorería UNASAM, Págalo.pe, Banco de la Nación o Transferencia bancaria.'])
        ->assertSeeTextInOrder(['Grupos sintéticos', 'Vacantes sintéticas'])
        ->assertSeeText('Descripción sintética del pie.')
        ->assertSeeTextInOrder(['Correo:', 'contacto@example.test', 'Horario de atención:', 'Lunes a viernes, 8:00 a 16:00'])
        ->assertSee('href="https://www.facebook.com/example"', false)
        ->assertDontSeeText('Costos según su vínculo con la UNASAM')
        ->assertDontSeeText('Huaraz, Áncash. Idiomas extranjeros');

    expect(DB::table('aud_registros')->where(['tabla' => 'tra_textos_portada', 'operacion' => 'UPDATE'])->count())->toBe(1);
});

it('hides a section and every link to it', function (): void {
    app(GuardarTextosDePortada::class)(portadaConCambios(['secciones' => seccionCambiada('oferta', ['visible' => false])]), $this->encargado);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('id="oferta"', false)
        ->assertDontSee('#oferta', false)
        ->assertDontSeeText('Horarios y vacantes')
        ->assertSee('href="'.route('portada').'#costos"', false);
});

it('drops the placement exam link from the learning path when the placement section is hidden', function (): void {
    app(GuardarTextosDePortada::class)(portadaConCambios(['secciones' => seccionCambiada('clasificacion', ['visible' => false])]), $this->encargado);

    $this->get('/')
        ->assertDontSee('id="clasificacion"', false)
        ->assertDontSee('#clasificacion', false)
        ->assertSeeText('Cada nivel agrupa varios ciclos.');
});

it('stores an uploaded cover photo, shows it and deletes the previous one when replaced', function (): void {
    $pagina = Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->fillForm(['foto_portada' => UploadedFile::fake()->image('portada.jpg', 2400, 1350)])
        ->call('guardar')
        ->assertHasNoFormErrors();
    $primera = app(ConsultarTextosDePortada::class)()->fotoPortada;

    expect($primera)->toStartWith('portada/')->toEndWith('.jpg');
    Storage::disk('public')->assertExists($primera);
    $this->get('/')->assertSee('src="'.Storage::disk('public')->url($primera).'"', false)->assertDontSee('images/portada/portada.jpg', false);

    $pagina->set('data.foto_portada', [])->fillForm(['foto_portada' => UploadedFile::fake()->image('nueva.png', 2400, 1350)])->call('guardar')->assertHasNoFormErrors();
    $segunda = app(ConsultarTextosDePortada::class)()->fotoPortada;

    expect($segunda)->not->toBe($primera)->toEndWith('.png');
    Storage::disk('public')->assertExists($segunda);
    Storage::disk('public')->assertMissing($primera);
});

it('keeps the cover photo when other texts are saved and falls back to the bundled one when it is removed', function (): void {
    app(GuardarTextosDePortada::class)(portadaConCambios(['foto_portada' => UploadedFile::fake()->image('portada.webp', 2400, 1350)]), $this->encargado);
    $foto = app(ConsultarTextosDePortada::class)()->fotoPortada;

    Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->fillForm(['pie' => 'Otra descripción sintética.'])
        ->call('guardar')
        ->assertHasNoFormErrors();
    expect(app(ConsultarTextosDePortada::class)()->fotoPortada)->toBe($foto);

    app(GuardarTextosDePortada::class)(portadaConCambios(['foto_portada' => null]), $this->encargado);

    expect(app(ConsultarTextosDePortada::class)()->fotoPortada)->toBeNull();
    Storage::disk('public')->assertMissing($foto);
    $this->get('/')->assertSee('src="'.asset('images/portada/portada.jpg').'"', false);
});

it('refuses a cover photo that is not a JPG, PNG or WebP image', function (): void {
    expect(fn () => app(GuardarTextosDePortada::class)(portadaConCambios(['foto_portada' => UploadedFile::fake()->create('portada.pdf', 100, 'application/pdf')]), $this->encargado))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La foto de portada debe ser una imagen JPG, PNG o WebP.');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('refuses links that are not https, too many links, unknown sections and texts over their limit', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarTextosDePortada::class)(portadaConCambios($cambios), $this->encargado))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'http link' => [['redes' => [['etiqueta' => 'Web', 'url' => 'http://example.test']]], 'Cada enlace del pie de página debe ser una dirección https://.'],
    'script link' => [['redes' => [['etiqueta' => 'Web', 'url' => 'javascript:alert(1)']]], 'Cada enlace del pie de página debe ser una dirección https://.'],
    'seven links' => [['redes' => array_fill(0, 7, ['etiqueta' => 'Web', 'url' => 'https://example.test'])], 'Registra hasta 6 enlaces en el pie de página.'],
    'unknown section' => [['secciones' => ['galeria' => ['etiqueta' => 'x', 'titulo' => 'x', 'introduccion' => null, 'visible' => true]]], 'La portada no tiene la sección galeria.'],
    'long button' => [['boton_principal' => str_repeat('a', 41)], 'Completa el botón principal, hasta 40 caracteres.'],
    'blank title' => [['secciones' => seccionCambiada('costos', ['titulo' => ' '])], 'Completa el título de la sección «Costos», hasta 120 caracteres.'],
    'bad email' => [['correo' => 'no-es-correo'], 'Escribe un correo de contacto válido.'],
]);

it('lets only staff who configure procedures save the cover and footer', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);

    expect(fn () => app(GuardarTextosDePortada::class)(portadaConCambios(['pie' => 'Intento sintético.']), $otro))->toThrow(AuthorizationException::class);
    expect(app(ConsultarTextosDePortada::class)()->pie)->toBe(TextosDeLaPortada::PIE);
});

it('gives each form section the id of its public section', function (): void {
    $this->actingAs($this->encargado)->get(EditarContenidoDePortada::getUrl())
        ->assertOk()
        ->assertSee('id="seccion-inicio"', false)
        ->assertSee('id="seccion-oferta"', false)
        ->assertSee('id="seccion-empieza"', false)
        ->assertSee('id="seccion-pie"', false);
});
