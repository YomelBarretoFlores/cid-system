<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\EliminarDisponibilidad;
use App\Modules\Docencia\Actions\GuardarDisponibilidad;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\EditDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers\DisponibilidadesRelationManager;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->docente = Docente::factory()->create();
});

function franjas(Docente $docente): array
{
    return $docente->disponibilidades()->orderBy('dia_semana')->orderBy('hora_inicio')->get()
        ->map(fn (Disponibilidad $franja): array => [$franja->dia_semana, $franja->hora_inicio, $franja->hora_fin])->all();
}

it('records weekly availability windows of a teacher', function (): void {
    app(GuardarDisponibilidad::class)($this->docente->id, 1, '08:00', '12:00');
    app(GuardarDisponibilidad::class)($this->docente->id, 1, '14:00', '18:00');
    app(GuardarDisponibilidad::class)($this->docente->id, 3, '18:00', '21:30');

    expect(franjas($this->docente))->toBe([[1, '08:00:00', '12:00:00'], [1, '14:00:00', '18:00:00'], [3, '18:00:00', '21:30:00']]);
});

it('edits and removes a window', function (): void {
    $franja = app(GuardarDisponibilidad::class)($this->docente->id, 2, '08:00', '10:00');

    app(GuardarDisponibilidad::class)($this->docente->id, 2, '09:00', '13:00', $franja->id);
    expect(franjas($this->docente))->toBe([[2, '09:00:00', '13:00:00']]);

    app(EliminarDisponibilidad::class)($this->docente->id, $franja->id);
    expect(franjas($this->docente))->toBe([]);
});

it('refuses an invalid or overlapping window and keeps the others', function (int $dia, string $inicio, string $fin, string $mensaje): void {
    app(GuardarDisponibilidad::class)($this->docente->id, 1, '08:00', '12:00');

    expect(fn () => app(GuardarDisponibilidad::class)($this->docente->id, $dia, $inicio, $fin))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(franjas($this->docente))->toBe([[1, '08:00:00', '12:00:00']]);
})->with([
    'overlap' => [1, '11:00', '13:00', 'La disponibilidad se cruza con otra franja del docente: Lunes de 08:00 a 12:00.'],
    'inside' => [1, '09:00', '10:00', 'La disponibilidad se cruza con otra franja del docente: Lunes de 08:00 a 12:00.'],
    'end before start' => [2, '10:00', '09:00', 'La hora de fin debe ser posterior a la de inicio.'],
    'unknown day' => [8, '10:00', '11:00', 'El día debe estar entre 1 (lunes) y 7 (domingo).'],
]);

it('accepts a window that only touches another one', function (): void {
    app(GuardarDisponibilidad::class)($this->docente->id, 1, '08:00', '12:00');
    app(GuardarDisponibilidad::class)($this->docente->id, 1, '12:00', '14:00');

    expect(franjas($this->docente))->toHaveCount(2);
});

it('does not touch the windows of another teacher', function (): void {
    $otro = Docente::factory()->create();
    $ajena = app(GuardarDisponibilidad::class)($otro->id, 1, '08:00', '12:00');

    expect(fn () => app(EliminarDisponibilidad::class)($this->docente->id, $ajena->id))->toThrow(ModelNotFoundException::class)
        ->and(franjas($otro))->toHaveCount(1);
});

it('audits the availability windows', function (): void {
    $franja = app(GuardarDisponibilidad::class)($this->docente->id, 1, '08:00', '12:00');
    app(EliminarDisponibilidad::class)($this->docente->id, $franja->id);

    expect(DB::table('aud_registros')->where('tabla', 'doc_disponibilidades')->orderBy('id')->pluck('operacion')->all())->toBe(['INSERT', 'DELETE']);
});

it('manages the availability from the teacher profile', function (): void {
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);

    Livewire::actingAs($gestor)->test(DisponibilidadesRelationManager::class, ['ownerRecord' => $this->docente, 'pageClass' => EditDocente::class])
        ->callAction(TestAction::make('agregarDisponibilidad')->table(), data: ['dia_semana' => 5, 'hora_inicio' => '07:00', 'hora_fin' => '09:00'])
        ->assertNotified('Disponibilidad agregada')
        ->callAction(TestAction::make('agregarDisponibilidad')->table(), data: ['dia_semana' => 5, 'hora_inicio' => '08:00', 'hora_fin' => '10:00'])
        ->assertNotified('La disponibilidad se cruza con otra franja del docente: Viernes de 07:00 a 09:00.')
        ->assertSee('Viernes');

    expect(franjas($this->docente))->toBe([[5, '07:00:00', '09:00:00']]);
});

it('shows the availability to readers without letting them change it', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);
    app(GuardarDisponibilidad::class)($this->docente->id, 4, '15:00', '19:00');

    Livewire::actingAs($lector)->test(DisponibilidadesRelationManager::class, ['ownerRecord' => $this->docente, 'pageClass' => EditDocente::class])
        ->assertSee('Jueves')
        ->assertActionHidden(TestAction::make('agregarDisponibilidad')->table());
});
