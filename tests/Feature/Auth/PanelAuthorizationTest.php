<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

it('creates the panel access permission with the migrations, without demo data', function (): void {
    expect(DB::table('permissions')->where(['name' => User::ADMIN_PANEL_PERMISSION, 'guard_name' => 'web'])->exists())->toBeTrue();
});

it('lets into the panel only users with the panel permission', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION))->get('/admin')->assertOk();
});

it('fails loudly instead of allowing an action whose policy method is missing', function (): void {
    expect(Filament::getPanel('admin')->isAuthorizationStrict())->toBeTrue();
});
