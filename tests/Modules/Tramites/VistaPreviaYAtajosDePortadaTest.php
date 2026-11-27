<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\ConsultarTextosDePortada;
use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Pages\EditarContenidoDePortada;
use App\Modules\Tramites\Filament\Pages\Schemas\FormularioDeContenidoDePortada;
use App\Modules\Tramites\Support\BorradorDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
});

function portadaConSeccionOculta(string $clave): array
{
    $textos = FormularioDeContenidoDePortada::estado(app(ConsultarTextosDePortada::class)());

    return [...$textos, 'secciones' => [$clave => [...TextosDeLaPortada::completas()[$clave], 'visible' => false]]];
}

it('previews the unsaved texts with a banner, without saving them nor caching or indexing the page', function (): void {
    Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->fillForm(['subtitulo' => 'para el borrador sintético.', 'pie' => 'Pie de borrador sintético.'])
        ->callAction('vistaPrevia')
        ->assertHasNoFormErrors();

    $this->actingAs($this->encargado)->get(route('panel.portada.vista-previa'))
        ->assertOk()
        ->assertSeeText('Vista previa: los cambios aún no se guardaron')
        ->assertSeeText('para el borrador sintético.')
        ->assertSeeText('Pie de borrador sintético.')
        ->assertDontSee('#seccion-', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'no-store, private');

    expect(app(ConsultarTextosDePortada::class)()->subtitulo)->toBe(TextosDeLaPortada::SUBTITULO);
    $this->get('/')->assertDontSeeText('para el borrador sintético.');
});

it('refuses a preview with texts that could not be saved', function (): void {
    Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->fillForm(['boton_principal' => str_repeat('a', 41)])
        ->callAction('vistaPrevia')
        ->assertHasFormErrors(['boton_principal']);

    $this->actingAs($this->encargado)->get(route('panel.portada.vista-previa'))->assertRedirect(EditarContenidoDePortada::getUrl());
});

it('keeps the preview to staff who may edit the content', function (): void {
    $this->get(route('panel.portada.vista-previa'))->assertRedirect(route('filament.admin.auth.login'));

    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);
    $this->actingAs($otro)->get(route('panel.portada.vista-previa'))->assertForbidden();
});

it('shows an edit shortcut to its form section on each visible section for staff who may edit the content', function (): void {
    app(GuardarTextosDePortada::class)(portadaConSeccionOculta('preguntas'), $this->encargado);
    $formulario = EditarContenidoDePortada::getUrl(panel: 'admin');

    $this->actingAs($this->encargado)->get('/')
        ->assertOk()
        ->assertSee('href="'.$formulario.'#seccion-inicio"', false)
        ->assertSee('href="'.$formulario.'#seccion-centro"', false)
        ->assertSee('href="'.$formulario.'#seccion-costos"', false)
        ->assertSee('href="'.$formulario.'#seccion-pie"', false)
        ->assertDontSee('#seccion-preguntas', false)
        ->assertSeeText('Editar sección Costos')
        ->assertSeeText('Editar sección Pie de página y contacto');
});

it('shows no edit shortcut to guests, students or staff who may not edit the content', function (?Closure $usuario): void {
    if ($usuario instanceof Closure) {
        $this->actingAs($usuario());
    }

    $this->get('/')->assertOk()->assertDontSee('#seccion-', false)->assertDontSeeText('Editar sección');
})->with([
    'guest' => [null],
    'student' => [function (): User {
        $estudiante = User::factory()->create();
        Persona::factory()->create(['user_id' => $estudiante->id]);

        return $estudiante;
    }],
    'staff without the permission' => [fn (): User => User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value])],
]);

it('shows the edited footer on the other public pages and no link to a section hidden on the home page', function (string $ruta): void {
    app(GuardarTextosDePortada::class)([...portadaConSeccionOculta('costos'), 'pie' => 'Pie compartido sintético.', 'correo' => 'contacto@example.test'], $this->encargado);

    $this->get(route($ruta))
        ->assertOk()
        ->assertSeeText('Pie compartido sintético.')
        ->assertSeeText('contacto@example.test')
        ->assertDontSee('#costos', false)
        ->assertSee('href="'.route('portada').'#preguntas"', false);
})->with(['privacidad', 'verificacion.documento', 'ingresar']);

it('cleans the preview lists with the same rules as saving', function (): void {
    $actuales = app(ConsultarTextosDePortada::class)();
    $estado = ['nosotros' => 'Presentación sintética.', 'pasos_matricula' => [], 'pasos_clasificacion' => [], 'niveles' => [], 'preguntas' => []];

    expect(fn () => BorradorDePortada::guardar($estado, $actuales))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Registra de 1 a 12 filas en los pasos de la matrícula.')
        ->and(BorradorDePortada::leer())->toBeNull();
});

it('still opens the privacy page with the fallback footer when the editable texts cannot be read', function (): void {
    Cache::flush();
    DB::statement('ALTER TABLE tra_textos_portada RENAME TO tra_textos_portada_fuera');

    $this->get(route('privacidad'))
        ->assertOk()
        ->assertSeeText('Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos por nivel.');
});
