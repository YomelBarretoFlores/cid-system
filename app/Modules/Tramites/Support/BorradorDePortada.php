<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Models\TextosDePortada;
use Illuminate\Http\UploadedFile;

// The unsaved panel texts kept in the editor's own session, so the preview tab shows them without touching the saved row.
final class BorradorDePortada
{
    private const string CLAVE = 'tramites.portada.borrador';

    /**
     * A new photo is only stored on saving, so the preview keeps showing the saved one.
     *
     * @param  array<string, mixed>  $estado  the form state, already checked by the form rules
     */
    public static function guardar(array $estado, TextosDeLaPortada $actuales): void
    {
        $fotoNueva = $estado['foto_portada'] ?? null;
        $fotoPendiente = $fotoNueva instanceof UploadedFile;

        session()->put(self::CLAVE, [
            'textos' => [
                ...new LimpiezaDeTextosDePortada($actuales)->todo($estado),
                'foto_portada' => $fotoPendiente ? $actuales->fotoPortada : FotoDePortada::elegida($fotoNueva, $actuales->fotoPortada),
            ],
            'foto_pendiente' => $fotoPendiente,
        ]);
    }

    /**
     * @return array{textos: TextosDeLaPortada, fotoPendiente: bool}|null
     */
    public static function leer(): ?array
    {
        $borrador = session()->get(self::CLAVE);

        if (! is_array($borrador) || ! is_array($borrador['textos'] ?? null)) {
            return null;
        }

        return [
            'textos' => TextosDeLaPortada::desde(new TextosDePortada()->forceFill($borrador['textos'])),
            'fotoPendiente' => (bool) ($borrador['foto_pendiente'] ?? false),
        ];
    }
}
