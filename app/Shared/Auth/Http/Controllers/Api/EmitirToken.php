<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

use App\Shared\Auth\Actions\EmitirTokenDeApi;
use App\Shared\Auth\Http\Requests\EmitirTokenDeApiRequest;
use App\Shared\Auth\Support\PerfilesDelUsuario;
use Illuminate\Http\JsonResponse;

final readonly class EmitirToken
{
    public function __construct(
        private EmitirTokenDeApi $emitirToken,
        private PerfilesDelUsuario $perfiles,
    ) {}

    /**
     * Issue a personal access token for an external client.
     *
     * @unauthenticated
     */
    public function __invoke(EmitirTokenDeApiRequest $request): JsonResponse
    {
        $nuevo = ($this->emitirToken)($request->string('email')->toString(), $request->string('password')->toString(), (string) $request->ip(), $request->string('dispositivo')->toString());

        return response()->json([
            'token' => $nuevo->plainTextToken,
            'tipo' => 'Bearer',
            'expira_en' => $nuevo->accessToken->expires_at?->setTimezone('America/Lima')->toIso8601String(),
            'perfiles' => $this->perfiles->de((int) $nuevo->accessToken->tokenable_id),
        ], 201);
    }
}
