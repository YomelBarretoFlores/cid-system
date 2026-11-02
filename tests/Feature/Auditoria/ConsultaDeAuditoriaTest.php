<?php

declare(strict_types=1);

use App\Shared\Audit\Enums\PermisoAuditoria;
use App\Shared\Audit\Filament\Resources\Auditoria\Pages\ListRegistrosDeAuditoria;
use App\Shared\Audit\Filament\Resources\Auditoria\Pages\ViewRegistroDeAuditoria;
use App\Shared\Audit\Filament\Resources\Auditoria\RegistroDeAuditoriaResource;
use App\Shared\Audit\Models\RegistroDeAuditoria;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function auditor(): User
{
    return User::factory()->create(['name' => 'Ana Auditora'])->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAuditoria::Ver->value]);
}

/**
 * @param  array<string, mixed>  $datos
 */
function registroDeAuditoria(array $datos): RegistroDeAuditoria
{
    $id = DB::table('aud_registros')->insertGetId([
        'tabla' => 'aca_aulas',
        'registro_id' => 1,
        'operacion' => 'UPDATE',
        'datos_anteriores' => null,
        'datos_nuevos' => null,
        'usuario_id' => null,
        'ip' => null,
        'ocurrido_en' => '2030-01-01 12:00:00+00',
        ...array_map(fn (mixed $valor): mixed => is_array($valor) ? json_encode($valor) : $valor, $datos),
    ]);

    return RegistroDeAuditoria::query()->findOrFail($id);
}

it('refuses the audit log to staff without the audit permission', function (): void {
    $registro = registroDeAuditoria([]);
    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoUsuario::Gestionar->value]));

    $this->get(RegistroDeAuditoriaResource::getUrl('index'))->assertForbidden();
    $this->get(RegistroDeAuditoriaResource::getUrl('view', ['record' => $registro]))->assertForbidden();
});

it('lists the newest changes first with their table, operation, user and IP', function (): void {
    $auditor = auditor();
    $antiguo = registroDeAuditoria(['ocurrido_en' => '2030-01-01 12:00:00+00', 'tabla' => 'per_personas', 'operacion' => 'INSERT']);
    $reciente = registroDeAuditoria(['ocurrido_en' => '2030-01-02 15:30:45+00', 'tabla' => 'tabla_sin_etiqueta', 'operacion' => 'DELETE', 'usuario_id' => $auditor->id, 'ip' => '10.0.0.7', 'registro_id' => 77]);
    $this->actingAs($auditor);

    $this->get(RegistroDeAuditoriaResource::getUrl('index'))->assertOk();
    Livewire::test(ListRegistrosDeAuditoria::class)
        ->assertCanSeeTableRecords([$reciente, $antiguo], inOrder: true)
        ->assertTableColumnFormattedStateSet('ocurrido_en', '02/01/2030 10:30:45', $reciente)
        ->assertTableColumnFormattedStateSet('tabla', 'Personas', $antiguo)
        ->assertTableColumnFormattedStateSet('tabla', 'tabla_sin_etiqueta', $reciente)
        ->assertTableColumnFormattedStateSet('operacion', 'Alta', $antiguo)
        ->assertTableColumnFormattedStateSet('operacion', 'Baja', $reciente)
        ->assertTableColumnStateSet('usuario.name', 'Ana Auditora', $reciente)
        ->assertTableColumnStateSet('ip', '10.0.0.7', $reciente)
        ->assertTableColumnStateSet('registro_id', 77, $reciente);
});

it('filters the audit log by table, operation, user, date and record', function (): void {
    $auditor = auditor();
    $aula = registroDeAuditoria(['tabla' => 'aca_aulas', 'operacion' => 'UPDATE', 'registro_id' => 5, 'usuario_id' => $auditor->id, 'ocurrido_en' => '2030-03-10 04:00:00+00']);
    $persona = registroDeAuditoria(['tabla' => 'per_personas', 'operacion' => 'INSERT', 'registro_id' => 6, 'ocurrido_en' => '2030-03-10 06:00:00+00']);
    $this->actingAs($auditor);

    Livewire::test(ListRegistrosDeAuditoria::class)
        ->filterTable('tabla', 'per_personas')
        ->assertCanSeeTableRecords([$persona])->assertCanNotSeeTableRecords([$aula])
        ->resetTableFilters()
        ->filterTable('operacion', 'UPDATE')
        ->assertCanSeeTableRecords([$aula])->assertCanNotSeeTableRecords([$persona])
        ->resetTableFilters()
        ->filterTable('usuario_id', $auditor->id)
        ->assertCanSeeTableRecords([$aula])->assertCanNotSeeTableRecords([$persona])
        ->resetTableFilters()
        ->filterTable('registro_id', ['registro_id' => 6])
        ->assertCanSeeTableRecords([$persona])->assertCanNotSeeTableRecords([$aula])
        ->resetTableFilters()
        // 04:00 UTC is still 9 March in Lima; 06:00 UTC is 10 March.
        ->filterTable('ocurrido_en', ['desde' => '2030-03-10', 'hasta' => '2030-03-10'])
        ->assertCanSeeTableRecords([$persona])->assertCanNotSeeTableRecords([$aula]);
});

it('shows only the changed fields of a record and hides the sensitive ones', function (): void {
    $registro = registroDeAuditoria([
        'tabla' => 'doc_docentes',
        'operacion' => 'UPDATE',
        'datos_anteriores' => ['id' => 3, 'categoria' => 'auxiliar', 'cuenta_bancaria' => 'cifrado-anterior', 'activo' => true],
        'datos_nuevos' => ['id' => 3, 'categoria' => 'principal', 'cuenta_bancaria' => 'cifrado-nuevo', 'activo' => true],
    ]);
    $this->actingAs(auditor());

    $this->get(RegistroDeAuditoriaResource::getUrl('view', ['record' => $registro]))->assertOk();
    Livewire::test(ViewRegistroDeAuditoria::class, ['record' => $registro->getRouteKey()])
        ->assertSeeInOrder(['Campo', 'Antes', 'Después', 'categoria', 'auxiliar', 'principal', 'cuenta_bancaria', '(oculto)', '(oculto)'])
        ->assertDontSee('cifrado-anterior')
        ->assertDontSee('cifrado-nuevo')
        ->assertDontSee('activo');
});

it('shows only the new values of an insert', function (): void {
    $registro = registroDeAuditoria(['operacion' => 'INSERT', 'datos_nuevos' => ['id' => 8, 'nombre' => 'Laboratorio 2']]);
    $this->actingAs(auditor());

    Livewire::test(ViewRegistroDeAuditoria::class, ['record' => $registro->getRouteKey()])
        ->assertSeeInOrder(['Campo', 'Después', 'nombre', 'Laboratorio 2'])
        ->assertDontSee('Antes');
});

it('offers no way to create, edit or delete audit records', function (): void {
    $this->actingAs(auditor());

    expect(array_keys(RegistroDeAuditoriaResource::getPages()))->toBe(['index', 'view'])
        ->and(collect(Route::getRoutes()->getRoutesByName())->keys()->filter(fn (string $nombre): bool => str_contains($nombre, 'resources.auditoria.'))->values()->all())
        ->toEqualCanonicalizing(['filament.admin.resources.auditoria.index', 'filament.admin.resources.auditoria.view'])
        ->and(RegistroDeAuditoriaResource::canCreate())->toBeFalse()
        ->and(RegistroDeAuditoriaResource::canEdit(registroDeAuditoria([])))->toBeFalse()
        ->and(RegistroDeAuditoriaResource::canDelete(registroDeAuditoria([])))->toBeFalse();
});
