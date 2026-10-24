<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Spatie\Permission\Models\Permission;

it('shows the admin login page to guests', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('denies the admin panel to users without the access permission', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

it('grants the admin panel to users with the access permission', function (): void {
    Permission::findOrCreate(User::ADMIN_PANEL_PERMISSION);
    $user = User::factory()->create();
    $user->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});
