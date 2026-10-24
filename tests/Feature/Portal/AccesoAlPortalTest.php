<?php

declare(strict_types=1);

use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Spatie\Permission\Models\Permission;

dataset('rutas del portal', [
    'inicio' => fn (): string => route('estudiante.inicio'),
    'pagos' => fn (): string => route('estudiante.pagos'),
    'recibo' => fn (): string => route('estudiante.pagos.recibo', Pago::factory()->create()),
    'matricula' => fn (): string => route('estudiante.matricula'),
    'matriculas' => fn (): string => route('estudiante.matriculas'),
]);

it('redirects guests of every portal route to the portal login', function (string $ruta): void {
    $this->get($ruta)->assertRedirect(route('estudiante.ingresar'));
})->with('rutas del portal');

it('sends unverified students of every portal route to the verification notice', function (string $ruta): void {
    $user = User::factory()->unverified()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get($ruta)->assertRedirect(route('verification.notice'));
})->with('rutas del portal');

it('forbids every portal route to a verified user without a person record', function (string $ruta): void {
    $this->actingAs(User::factory()->create())->get($ruta)->assertForbidden();
})->with('rutas del portal');

it('keeps redirecting admin guests to the admin login', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('still lets staff with the panel permission reach the admin panel, and denies it to a student', function (): void {
    Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION);
    $staff = User::factory()->create();
    $staff->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $this->actingAs($staff)->get('/admin')->assertOk();

    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);
    $this->actingAs($estudiante)->get('/admin')->assertForbidden();
});

it('ends an open portal session once the password changes elsewhere', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('estudiante.inicio'))->assertOk();
    $user->forceFill(['password' => 'otra-clave99'])->save();

    $this->get(route('estudiante.inicio'))->assertRedirect(route('estudiante.ingresar'));
    $this->assertGuest();
});
