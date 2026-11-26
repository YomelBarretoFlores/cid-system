<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Models\Adjunto;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Auth\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('local');
    $this->estudiante = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->estudiante->id]);
    $tipo = TipoTramite::factory()->sinTasa()->create([
        'nombre' => 'Otro', 'accion_final' => AccionFinal::Ninguna,
        'campos' => [['clave' => 'exposicion', 'obligatorio' => true], ['clave' => 'celular', 'obligatorio' => true]],
        'adjuntos' => [['nombre' => 'Copia de DNI', 'obligatorio' => true]],
    ]);
    $this->tramite = app(SolicitarTramite::class)($this->persona->id, $tipo->id, new DatosDeSolicitud(
        exposicion: 'Solicito una constancia de estudios para mi trabajo.',
        celular: '987654321',
        adjuntos: [UploadedFile::fake()->createWithContent('dni.pdf', "%PDF-1.4\n%%EOF\n")],
    ));
    $this->adjunto = Adjunto::query()->where('tramite_id', $this->tramite->id)->sole();
    $this->personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);
});

it('shows staff the request fields by their FUT labels and lists the attachments', function (): void {
    $this->actingAs($this->personal);

    Livewire::test(ViewTramite::class, ['record' => $this->tramite->getRouteKey()])
        ->assertSeeTextInOrder(['Solicitud', 'Y expongo que', 'Solicito una constancia de estudios para mi trabajo.', 'Celular', '987654321', 'Adjuntos', 'Copia de DNI', 'Descargar']);
});

it('lets staff download an attachment and keeps it from students and other staff', function (): void {
    $this->actingAs($this->personal)->get(route('panel.tramites.adjuntos', $this->adjunto->ulid))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->actingAs($this->estudiante)->get(route('panel.tramites.adjuntos', $this->adjunto->ulid))->assertForbidden();

    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->actingAs($sinPermiso)->get(route('panel.tramites.adjuntos', $this->adjunto->ulid))->assertForbidden();
});
