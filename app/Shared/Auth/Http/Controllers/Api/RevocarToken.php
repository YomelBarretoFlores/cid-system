<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

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
        $token = PersonalAccessToken::findToken((string) $request->bearerToken());
        // Only the token of the authenticated account counts, whatever header a request also carries.
        $token = $token?->tokenable_id === $request->user()?->getAuthIdentifier() ? $token : null;

        if (! $token instanceof PersonalAccessToken) {
            return response()->json(['message' => 'Esta sesión no usa un token que se pueda revocar.'], 409);
        }

        $token->delete();

        return response()->noContent();
    }
}
