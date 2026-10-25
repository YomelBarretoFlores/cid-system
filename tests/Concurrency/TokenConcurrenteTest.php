<?php

declare(strict_types=1);

use App\Shared\Auth\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('keeps one live token per device when the same device signs in twice at once', function (): void {
    User::factory()->create(['email' => 'rosa@ciln.test']);

    $resultados = enParalelo('emitir-token.php', [['rosa@ciln.test', '10.0.0.1'], ['rosa@ciln.test', '10.0.0.2']]);

    expect($resultados)->toBe(['emitido', 'emitido'])
        ->and(PersonalAccessToken::query()->where('name', 'App móvil')->count())->toBe(1);
});
