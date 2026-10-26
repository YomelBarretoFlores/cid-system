<?php

declare(strict_types=1);

use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Livewire\IniciarSesion;
use App\Shared\Auth\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function intentarIngreso(string $email, string $password): Testable
{
    return Livewire::test(IniciarSesion::class)
        ->set('email', $email)
        ->set('password', $password)
        ->call('ingresar');
}

it('shows the portal login page to guests', function (): void {
    $this->get(route('ingresar'))
        ->assertOk()
        ->assertSee('Ingresar al portal del CILN');
});

it('logs a student in with a valid email and password', function (): void {
    $user = User::factory()->create(['email' => 'ana@correo.test']);

    intentarIngreso('  ANA@correo.test ', 'password')
        ->assertHasNoErrors()
        ->assertRedirect(route('estudiante.inicio'));

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password with a message that does not reveal the account', function (): void {
    User::factory()->create(['email' => 'ana@correo.test']);

    intentarIngreso('ana@correo.test', 'otra-clave1')
        ->assertHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
    intentarIngreso('nadie@correo.test', 'otra-clave1')
        ->assertHasErrors(['email' => 'El correo o la contraseña no son correctos.']);

    $this->assertGuest();
});

it('blocks the sixth attempt within a minute even with the right password', function (): void {
    User::factory()->create(['email' => 'ana@correo.test']);

    foreach (range(1, 5) as $intento) {
        intentarIngreso('ana@correo.test', "mala-clave{$intento}");
    }

    $componente = intentarIngreso('ana@correo.test', 'password');

    expect($componente->errors()->first('email'))->toStartWith('Demasiados intentos. Vuelve a intentarlo en');
    $this->assertGuest();
});

it('sends guests of portal pages to the portal sign-in', function (): void {
    $this->get(route('estudiante.inicio'))->assertRedirect(route('ingresar'));
});

it('keeps sending guests of the admin panel to the admin login', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('sends a signed-in user away from the login page', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('ingresar'))
        ->assertRedirect(route('estudiante.inicio'));
});

it('asks an unverified user to verify the email before entering the portal', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('estudiante.inicio'))
        ->assertRedirect(route('verification.notice'));
});

it('logs the student out and ends the session', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('estudiante.salir'))
        ->assertRedirect(route('ingresar'));

    $this->assertGuest();
});

it('only logs out through a POST request', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/estudiante/salir')
        ->assertMethodNotAllowed();
});

it('stores emails in lowercase so a mixed-case address can still log in', function (): void {
    $user = User::factory()->create(['email' => ' Ana@Correo.TEST ']);

    expect($user->email)->toBe('ana@correo.test');

    intentarIngreso('ana@correo.test', 'password')->assertHasNoErrors();
    $this->assertAuthenticatedAs($user);
});

it('blocks an address that keeps trying different accounts', function (): void {
    foreach (range(1, 20) as $intento) {
        intentarIngreso("persona{$intento}@correo.test", 'mala-clave1');
    }

    $componente = intentarIngreso('otra@correo.test', 'mala-clave1');

    expect($componente->errors()->first('email'))->toStartWith('Demasiados intentos. Vuelve a intentarlo en');
});

it('ends the other sessions of a student after a password change', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))->assertOk();

    $user->forceFill(['password' => 'otra-clave9'])->save();

    $this->get(route('estudiante.inicio'))->assertRedirect(route('ingresar'));
    $this->assertGuest();
});

it('sends signed-in staff from the portal sign-in to the admin panel', function (): void {
    Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION);
    $staff = User::factory()->create();
    $staff->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($staff)
        ->get(route('ingresar'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));
});
