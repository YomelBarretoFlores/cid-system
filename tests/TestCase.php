<?php

declare(strict_types=1);

namespace Tests;

use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Auth\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // actingAs() stands for staff already signed in through the panel; tests of that sign-in use be().
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($user instanceof User && $user->getAppAuthenticationSecret() === null && $user->can(User::ADMIN_PANEL_PERMISSION)) {
            $user->saveAppAuthenticationSecret(app(AppAuthentication::class)->generateSecret());
        }

        $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => now()->getTimestamp()]);

        return parent::actingAs($user, $guard);
    }
}
