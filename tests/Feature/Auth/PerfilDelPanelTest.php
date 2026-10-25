<?php

declare(strict_types=1);

use App\Shared\Auth\Filament\EditarPerfil;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->usuario = User::factory()->create(['name' => 'Ana Torres', 'email' => 'ana@ciln.test'])
        ->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->actingAs($this->usuario);
});

it('registers the profile page in the panel', function (): void {
    expect(Filament::getPanel('admin')->getProfilePage())->toBe(EditarPerfil::class)
        ->and(Filament::getPanel('admin')->hasProfile())->toBeTrue();
});

it('shows the name and the e-mail as read-only and never saves them', function (): void {
    Livewire::test(EditarPerfil::class)
        ->assertFormFieldDisabled('name')
        ->assertFormFieldDisabled('email')
        ->set('data.name', 'Otro nombre')
        ->set('data.email', 'otro@ciln.test')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->usuario->fresh())->name->toBe('Ana Torres')->email->toBe('ana@ciln.test');
});

it('asks the current password before changing it', function (): void {
    Livewire::test(EditarPerfil::class)
        ->fillForm(['password' => 'nueva-clave-2027', 'passwordConfirmation' => 'nueva-clave-2027'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword' => 'required'])
        ->fillForm(['password' => 'nueva-clave-2027', 'passwordConfirmation' => 'nueva-clave-2027', 'currentPassword' => 'equivocada'])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check('password', $this->usuario->fresh()->password))->toBeTrue();
});

it('changes the password with the current one', function (): void {
    Livewire::test(EditarPerfil::class)
        ->fillForm(['password' => 'nueva-clave-2027', 'passwordConfirmation' => 'nueva-clave-2027', 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Hash::check('nueva-clave-2027', $this->usuario->fresh()->password))->toBeTrue();
});

it('asks the current password only when a new one is typed', function (): void {
    Livewire::test(EditarPerfil::class)
        ->assertFormFieldHidden('currentPassword')
        ->fillForm(['password' => 'nueva-clave-2027'])
        ->assertFormFieldVisible('currentPassword');
});

it('closes the other sessions of the account after a password change', function (): void {
    $anterior = $this->usuario->password;

    Livewire::test(EditarPerfil::class)
        ->fillForm(['password' => 'nueva-clave-2027', 'passwordConfirmation' => 'nueva-clave-2027', 'currentPassword' => 'password'])
        ->call('save');

    $this->withSession(['password_hash_web' => $anterior])->get('/admin/profile')->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('opens the profile from the panel', function (): void {
    $this->get('/admin/profile')->assertOk()->assertSee('Ana Torres');
});

it('rejects a weak password or a different confirmation', function (): void {
    Livewire::test(EditarPerfil::class)
        ->fillForm(['password' => 'corta', 'passwordConfirmation' => 'corta', 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasFormErrors(['password'])
        ->fillForm(['password' => 'nueva-clave-2027', 'passwordConfirmation' => 'otra-clave-2027', 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasFormErrors(['password']);

    expect(Hash::check('password', $this->usuario->fresh()->password))->toBeTrue();
});
