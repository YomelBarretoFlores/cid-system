<?php

declare(strict_types=1);

namespace App\Shared\Audit\Support;

use Illuminate\Support\Str;

// The audit trail stores whole rows; the screen shows what changed and never a secret.
final class CambiosDelRegistro
{
    private const array SENSIBLES = ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes', 'cuenta_bancaria'];

    /**
     * @param  array<string, mixed>|null  $antes
     * @param  array<string, mixed>|null  $despues
     * @return list<array{campo: string, antes: ?string, despues: ?string}>
     */
    public static function calcular(?array $antes, ?array $despues): array
    {
        $campos = array_map(strval(...), array_keys([...$antes ?? [], ...$despues ?? []]));
        $cambios = [];

        foreach ($campos as $campo) {
            if ($antes !== null && $despues !== null && ($antes[$campo] ?? null) === ($despues[$campo] ?? null)) {
                continue;
            }

            $cambios[] = [
                'campo' => $campo,
                'antes' => $antes === null ? null : self::mostrar($campo, $antes[$campo] ?? null),
                'despues' => $despues === null ? null : self::mostrar($campo, $despues[$campo] ?? null),
            ];
        }

        return $cambios;
    }

    private static function mostrar(string $campo, mixed $valor): string
    {
        if (in_array($campo, self::SENSIBLES, true) || Str::contains($campo, ['token', 'secret'])) {
            return '(oculto)';
        }

        return match (true) {
            $valor === null => '(vacío)',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            is_scalar($valor) => (string) $valor,
            default => (string) json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
