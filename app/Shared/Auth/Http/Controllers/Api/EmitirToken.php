<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers\Api;

use App\Shared\Auth\Actions\EmitirTokenDeApi;
use App\Shared\Auth\Http\Requests\EmitirTokenDeApiRequest;
use Illuminate\Http\JsonResponse;

final readonly class EmitirToken
{
    public function __construct(private EmitirTokenDeApi $emitirToken) {}

    /**
     * Issue a personal access token for an external client.
     *
     * @unauthenticated
     */
    public function __invoke(EmitirTokenDeApiRequest $request): JsonResponse
    {
        $token = ($this->emitirToken)($request->string('email')->toString(), $request->string('password')->toString(), (string) $request->ip(), $request->string('dispositivo')->toString());

        return response()->json([
            'token' => $token->token,
            'tipo' => 'Bearer',
            'expira_en' => $token->expiraEn->setTimezone('America/Lima')->toIso8601String(),
            'perfiles' => $token->perfiles,
        ], 201);
    }
}
