<?php

declare(strict_types=1);

namespace Tests;

use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    // actingAs() stands for a session opened through the panel sign-in; tests of that sign-in use be().
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        $this->withSession([ProtegerSesionDelPanel::ULTIMA_ACTIVIDAD => ProtegerSesionDelPanel::marca($user->getAuthIdentifier())]);

        return parent::actingAs($user, $guard);
    }
}
