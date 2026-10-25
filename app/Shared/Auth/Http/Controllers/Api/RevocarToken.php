<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

use App\Shared\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevocarToken
{
    /**
     * Revoke the token used for this request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $token = PersonalAccessToken::findToken((string) $request->bearerToken());

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
