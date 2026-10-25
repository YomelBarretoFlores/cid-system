<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\PerfilesDelUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class MostrarSesion
{
    public function __construct(private PerfilesDelUsuario $perfiles) {}

    /**
     * The profiles of the account behind the token, so the client knows which endpoints it may call.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $token = PersonalAccessToken::findToken((string) $request->bearerToken());

        return response()->json([
            'dispositivo' => $token instanceof PersonalAccessToken ? $token->name : null,
            'expira_en' => $token instanceof PersonalAccessToken ? $token->expires_at?->setTimezone('America/Lima')->toIso8601String() : null,
            'perfiles' => $this->perfiles->de($usuario->id),
        ]);
    }
}
