<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

// The personal token of the authenticated account in this request; a token of another account never counts.
final class TokenDeLaSesion
{
    public static function de(Request $request): ?PersonalAccessToken
    {
        $usuario = $request->user();
        $token = PersonalAccessToken::findToken((string) $request->bearerToken());

        return $usuario !== null
            && $token instanceof PersonalAccessToken
            && $token->tokenable_type === $usuario->getMorphClass()
            && (string) $token->tokenable_id === (string) $usuario->getAuthIdentifier()
            ? $token
            : null;
    }
}
