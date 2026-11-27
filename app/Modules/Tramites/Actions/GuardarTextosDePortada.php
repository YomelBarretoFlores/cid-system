<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\TextosDePortada;
use App\Modules\Tramites\Support\FotoDePortada;
use App\Modules\Tramites\Support\LimpiezaDeTextosDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class GuardarTextosDePortada
{
    public function __construct(private ConsultarTextosDePortada $consultar) {}

    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoTramite::Configurar->value);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): TextosDePortada
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $actuales = ($this->consultar)();
        $limpios = new LimpiezaDeTextosDePortada($actuales)->todo($datos);
        $foto = array_key_exists('foto_portada', $datos) ? FotoDePortada::elegida($datos['foto_portada'], $actuales->fotoPortada) : $actuales->fotoPortada;
        $nueva = $foto !== $actuales->fotoPortada ? $foto : null;

        try {
            [$textos, $anterior] = Transaccion::ejecutar(fn (): array => $this->guardar([...$limpios, 'foto_portada' => $foto]));
        } catch (Throwable $error) {
            FotoDePortada::borrar($nueva);

            throw $error;
        }

        // Both run against the caller's transaction when there is one; afterCommit runs at once otherwise.
        DB::afterRollBack(fn () => FotoDePortada::borrar($nueva));
        DB::afterCommit(fn () => FotoDePortada::borrar($anterior === $foto ? null : $anterior));

        return $textos;
    }

    /**
     * @param  array<string, mixed>  $limpios
     * @return array{TextosDePortada, string|null} the saved row and the photo it held before
     */
    private function guardar(array $limpios): array
    {
        $textos = TextosDePortada::query()->whereKey(TextosDePortada::FILA)->lockForUpdate()->first() ?? new TextosDePortada;
        $anterior = $textos->foto_portada;
        $textos->forceFill(['id' => TextosDePortada::FILA, ...$limpios])->save();

        return [$textos, $anterior];
    }
}
