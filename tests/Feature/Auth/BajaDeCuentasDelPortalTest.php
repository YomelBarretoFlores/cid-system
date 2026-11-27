<?php

declare(strict_types=1);

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages\ListCuentasDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Actions\AutenticarUsuario;
use App\Shared\Auth\Exceptions\CredencialesIncorrectas;
use App\Shared\Auth\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->personal = User::factory()->create()->givePermissionTo([
        User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value, PermisoPersona::Gestionar->value, ...PermisoDocencia::valores(),
    ]);
    $this->cuenta = User::factory()->create(['email' => 'rosa@correo.test']);
    $this->persona = Persona::factory()->create(['user_id' => $this->cuenta->id]);
});

function paginaDePersona(User $usuario, Persona $persona): Testable
{
    return Livewire::actingAs($usuario)->test(ViewPersona::class, ['record' => $persona->getRouteKey()]);
}

it('deactivates a portal account from the person page with a reason, closing its sessions and refusing its sign-in', function (): void {
    $this->cuenta->createToken('App móvil');

    paginaDePersona($this->personal, $this->persona)
        ->callAction('darDeBajaLaCuenta', data: ['motivo' => 'Cuenta duplicada'])
        ->assertHasNoActionErrors()
        ->assertNotified('Cuenta dada de baja');

    expect($this->cuenta->fresh()?->desactivado_en)->not->toBeNull()
        ->and($this->cuenta->tokens()->count())->toBe(0)
        ->and($this->persona->fresh()?->user_id)->toBe($this->cuenta->id)
        ->and(fn () => app(AutenticarUsuario::class)('rosa@correo.test', 'password', '127.0.0.1'))->toThrow(CredencialesIncorrectas::class);
});

it('asks for the reason of the deactivation', function (): void {
    paginaDePersona($this->personal, $this->persona)
        ->callAction('darDeBajaLaCuenta', data: ['motivo' => ''])
        ->assertHasActionErrors(['motivo' => 'required']);

    expect($this->cuenta->fresh()?->desactivado_en)->toBeNull();
});

it('reactivates a deactivated portal account so it can sign in again', function (): void {
    $this->cuenta->forceFill(['desactivado_en' => now()])->save();

    paginaDePersona($this->personal, $this->persona)
        ->assertActionHidden('darDeBajaLaCuenta')
        ->callAction('reactivarLaCuenta')
        ->assertNotified('Cuenta reactivada');

    expect($this->cuenta->fresh()?->desactivado_en)->toBeNull()
        ->and(app(AutenticarUsuario::class)('rosa@correo.test', 'password', '127.0.0.1')->id)->toBe($this->cuenta->id);
});

it('leaves staff accounts to Usuarios del personal', function (): void {
    $this->cuenta->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    paginaDePersona($this->personal, $this->persona)
        ->callAction('darDeBajaLaCuenta', data: ['motivo' => 'Dejó el CILN'])
        ->assertNotified('Es una cuenta del personal: gestiónala desde Usuarios del personal.');

    expect($this->cuenta->fresh()?->desactivado_en)->toBeNull();
});

it('does not offer the deactivation to staff who cannot manage people', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]);

    paginaDePersona($lector, $this->persona)->assertActionHidden('darDeBajaLaCuenta');
});

it('deactivates and reactivates a teacher account from the teacher accounts section', function (): void {
    $docente = Docente::factory()->create(['persona_id' => $this->persona->id]);
    $pagina = fn () => Livewire::actingAs($this->personal)->test(ListCuentasDocentes::class);

    $pagina()->callAction(TestAction::make('darDeBaja')->table($docente), data: ['motivo' => 'Dejó de enseñar en el CILN'])
        ->assertNotified('Cuenta dada de baja');
    expect($this->cuenta->fresh()?->desactivado_en)->not->toBeNull();

    $pagina()->assertTableColumnStateSet('estado_de_cuenta', 'Dada de baja', $docente)
        ->callAction(TestAction::make('reactivar')->table($docente))
        ->assertNotified('Cuenta reactivada');
    expect($this->cuenta->fresh()?->desactivado_en)->toBeNull();
});

it('keeps deactivated portal accounts out of the staff list', function (): void {
    $this->cuenta->forceFill(['desactivado_en' => now()])->save();
    $personalDeBaja = User::factory()->create(['desactivado_en' => now()])->assignRole(Role::findOrCreate('Caja', 'web'));

    Livewire::actingAs($this->personal->givePermissionTo('usuarios.gestionar'))->test(ListUsuarios::class)
        ->assertCanSeeTableRecords([$personalDeBaja])
        ->assertCanNotSeeTableRecords([$this->cuenta]);
});
