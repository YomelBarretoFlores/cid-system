<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Actions;

use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Enums\Parametro;
use App\Shared\Parametros\Models\Parametros;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarParametrosDelSistema
{
    public function __construct(private ParametrosDelSistema $parametrosDelSistema) {}

    // The values reach every module, so only the system administrators (who manage accounts and roles) change them.
    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoUsuario::Gestionar->value);
    }

    /**
     * @param  array<string, mixed>  $datos  keyed by the Parametro column; a missing key keeps its current value
     */
    public function __invoke(array $datos, User $usuario): Parametros
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $valores = $this->validar([...($this->parametrosDelSistema)()->comoArreglo(), ...$datos]);

        $parametros = Transaccion::ejecutar(function () use ($valores): Parametros {
            $parametros = Parametros::query()->whereKey(Parametros::FILA)->lockForUpdate()->first() ?? new Parametros;
            $parametros->forceFill(['id' => Parametros::FILA, ...$valores])->save();

            return $parametros;
        });

        app()->forgetInstance(ParametrosDelSistema::class);

        return $parametros;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, int>
     */
    private function validar(array $datos): array
    {
        $valores = [];

        foreach (Parametro::cases() as $parametro) {
            $valores[$parametro->value] = $this->entero($parametro, $datos[$parametro->value] ?? null);
        }

        throw_if(
            $valores[Parametro::ConfirmacionMinutosDespuesDelFin->value] < $valores[Parametro::CierreCierraMinutosDespuesDelFin->value],
            ReglaDeNegocioIncumplida::class,
            'El plazo de confirmación del delegado no puede vencer antes de que cierre el llamado de cierre.',
        );

        return $valores;
    }

    private function entero(Parametro $parametro, mixed $valor): int
    {
        $texto = is_int($valor) ? (string) $valor : (is_string($valor) ? trim($valor) : '');
        $valido = preg_match('/^\d{1,5}$/', $texto) === 1 && (int) $texto >= $parametro->minimo() && (int) $texto <= $parametro->maximo();

        throw_unless($valido, ReglaDeNegocioIncumplida::class, "{$parametro->etiqueta()} debe ser un número entero de {$parametro->minimo()} a {$parametro->maximo()}.");

        return (int) $texto;
    }
}
