<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\CrearCuentaDelPortal;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\CorreoDeRestablecimiento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    Notification::fake();
    $this->personal = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value, PermisoPersona::Gestionar->value]);
});

it('creates the portal account of a record on file from its page and emails the link to set the password', function (): void {
    $persona = Persona::factory()->create(['correo' => ' Rosa@Correo.TEST ']);

    Livewire::actingAs($this->personal)->test(ViewPersona::class, ['record' => $persona->getRouteKey()])
        ->callAction('crearCuentaDelPortal')
        ->assertHasNoActionErrors()
        ->assertNotified('Cuenta creada y enlace enviado');

    $usuario = User::query()->where('email', 'rosa@correo.test')->sole();
    expect($persona->refresh()->user_id)->toBe($usuario->id)
        ->and($persona->habilitada_en)->not->toBeNull();
    Notification::assertSentTo($usuario, CorreoDeRestablecimiento::class);
});

it('refuses a record that already has an account, has no email or whose email another account uses', function (array $persona, string $mensaje): void {
    User::factory()->create(['email' => 'ocupado@correo.test']);
    $registro = Persona::factory()->create($persona);
    $antes = User::query()->count();

    expect(fn () => app(CrearCuentaDelPortal::class)($registro->id, $this->personal))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(User::query()->count())->toBe($antes);
    Notification::assertNothingSent();
})->with([
    'already has an account' => [['user_id' => fn (): int => User::factory()->create()->id], 'Esta persona ya tiene una cuenta del portal.'],
    'no email' => [['correo' => null], 'Registra primero el correo personal de la persona.'],
    'email in use' => [['correo' => 'ocupado@correo.test'], 'Ya existe una cuenta con ese correo.'],
]);

it('lets only staff who manage people create the account', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPersona::Ver->value]);
    $persona = Persona::factory()->create();

    expect(fn () => app(CrearCuentaDelPortal::class)($persona->id, $lector))->toThrow(AuthorizationException::class);
    Livewire::actingAs($lector)->test(ViewPersona::class, ['record' => $persona->getRouteKey()])->assertActionHidden('crearCuentaDelPortal');
    expect($persona->refresh()->user_id)->toBeNull();
});
