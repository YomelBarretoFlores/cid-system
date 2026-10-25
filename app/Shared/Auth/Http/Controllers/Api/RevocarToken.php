<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

use App\Shared\Auth\Support\TokenDeLaSesion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevocarToken
{
    /**
     * Revoke the token used for this request.
     */
    public function __invoke(Request $request): Response|JsonResponse
    {
        $token = TokenDeLaSesion::de($request);

        if (! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Esta sesión no usa un token que se pueda revocar.'], 409);
        }

        $token->delete();

        return response()->noContent();
    }
}
