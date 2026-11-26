<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Actions\EmitirCertificadosDelPeriodo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Actions\EmitirConstanciasDeNotasDelActa;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ViewActa;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Notifications\NotificationMailbox;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->emisor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'nombres' => 'Ana']);
    $this->beto = Persona::factory()->create(['apellido_paterno' => 'Bravo', 'nombres' => 'Beto']);
    $this->ciro = Persona::factory()->create(['apellido_paterno' => 'Cano', 'nombres' => 'Ciro']);
});

// One closed acta of cycle 1 with every given student passed.
function actaConEstudiantes(Persona ...$personas): Acta
{
    $primera = array_shift($personas);
    ActasFinales::historial($primera, 'Inglés', [[1, '14.00', 'aprobado']], estadoActa: 'abierta');
    $acta = Acta::query()->sole();

    foreach ($personas as $persona) {
        DB::table('cer_notas_finales')->insert([
            'acta_id' => $acta->id, 'matricula_id' => Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $acta->grupo_id])->id,
            'persona_id' => $persona->id, 'estudiante_nombre' => $persona->nombre_completo, 'estudiante_documento' => "DNI {$persona->numero_documento}",
            'notas' => '{}', 'promedio' => '15.00', 'estado_final' => 'aprobado', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    DB::table('cer_actas')->where('id', $acta->id)->update(['estado' => 'cerrada', 'validada_por' => User::factory()->create()->id, 'validada_en' => now()->subHour()]);

    return $acta->refresh();
}

function rechazarDocumentoDe(Persona $persona): void
{
    Documento::creating(function (Documento $documento) use ($persona): void {
        if ($documento->persona_id === $persona->id) {
            throw new ReglaDeNegocioIncumplida('No se pudo leer la ficha de la persona.');
        }
    });
}

function avisoConTitulo(string $titulo): ?Notification
{
    app(NotificationMailbox::class)->flush();
    $notificaciones = new Notifications;
    $notificaciones->mount();
    $notificaciones->pullNotificationsFromSession();

    return $notificaciones->notifications->first(fn (Notification $notificacion): bool => $notificacion->getTitle() === $titulo);
}

it('issues the grade records of an acta once and reports the students who already had one as omitted', function (): void {
    $acta = actaConEstudiantes($this->ana, $this->beto);
    app(EmitirConstanciaDeNotas::class)($this->beto->id, 'Inglés', NivelCurricular::Basico, $this->emisor);

    $primera = app(EmitirConstanciasDeNotasDelActa::class)($acta->id, $this->emisor);
    $segunda = app(EmitirConstanciasDeNotasDelActa::class)($acta->id, $this->emisor);

    expect(array_map(fn (Documento $documento): int => $documento->persona_id, $primera->emitidos))->toBe([$this->ana->id])
        ->and($primera->omitidos)->toBe(1)
        ->and($primera->fallidos)->toBe([])
        ->and($segunda->emitidos)->toBe([])
        ->and($segunda->omitidos)->toBe(2)
        ->and(Documento::query()->where('tipo', TipoDocumento::ConstanciaNotas)->count())->toBe(2);
});

it('goes on with the rest of the acta when one grade record is refused and reports it', function (): void {
    $acta = actaConEstudiantes($this->ana, $this->beto, $this->ciro);
    rechazarDocumentoDe($this->beto);

    $resumen = app(EmitirConstanciasDeNotasDelActa::class)($acta->id, $this->emisor);

    expect(array_map(fn (Documento $documento): int => $documento->persona_id, $resumen->emitidos))->toBe([$this->ana->id, $this->ciro->id])
        ->and($resumen->omitidos)->toBe(0)
        ->and($resumen->fallidos)->toBe(["{$this->beto->nombre_completo}: No se pudo leer la ficha de la persona."])
        ->and(Documento::query()->pluck('persona_id')->sort()->values()->all())->toBe([$this->ana->id, $this->ciro->id]);
});

it('shows the issued, omitted and refused grade records of an acta in the panel', function (): void {
    $acta = actaConEstudiantes($this->ana, $this->beto, $this->ciro);
    app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->emisor);
    rechazarDocumentoDe($this->beto);

    Livewire::actingAs($this->emisor)->test(ViewActa::class, ['record' => $acta->getRouteKey()])
        ->callAction('emitirConstancias');

    expect(avisoConTitulo('1 constancia emitida')?->getBody())
        ->toBe("Ya tenían una vigente: 1. No se emitieron: {$this->beto->nombre_completo}: No se pudo leer la ficha de la persona.");
});

it('goes on with the rest of the period when one certificate is refused and reports omitted and refused students', function (): void {
    $ciclos = [[1, '14.00', 'aprobado'], [2, '14.00', 'aprobado'], [3, '14.00', 'aprobado'], [4, '14.00', 'aprobado']];
    ActasFinales::historial($this->ana, 'Inglés', $ciclos);
    $periodo = Acta::query()->where('ciclo_numero', 4)->sole()->periodo_id;
    ActasFinales::historial($this->beto, 'Inglés', $ciclos, periodoId: $periodo);
    ActasFinales::historial($this->ciro, 'Inglés', $ciclos, periodoId: $periodo);
    $primera = app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor);
    Documento::query()->where('persona_id', $this->ciro->id)->update(['anulado_por' => $this->emisor->id, 'anulado_en' => now(), 'motivo_anulacion' => 'Se emitió con un error.']);
    rechazarDocumentoDe($this->ciro);

    $segunda = app(EmitirCertificadosDelPeriodo::class)($periodo, $this->emisor);

    expect($primera->emitidos)->toHaveCount(3)
        ->and($segunda->emitidos)->toBe([])
        ->and($segunda->omitidos)->toBe(2)
        ->and($segunda->fallidos)->toBe(["{$this->ciro->nombre_completo}: No se pudo leer la ficha de la persona."]);

    Livewire::actingAs($this->emisor)->test(ListDocumentos::class)
        ->callAction('emitirCertificadosDelPeriodo', ['periodo_id' => $periodo]);

    expect(avisoConTitulo('0 certificados emitidos')?->getBody())
        ->toBe("Ya tenían uno vigente: 2. No se emitieron: {$this->ciro->nombre_completo}: No se pudo leer la ficha de la persona.");
});
