<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Modules\Tramites\Filament\Pages\EditarContenidoDePortada;
use App\Modules\Tramites\Filament\Pages\Schemas\FormularioDeContenidoDePortada;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

// The «Editar» links of the home page, only for staff who can open the content form; built per visit, never cached.
final readonly class AtajosDeEdicion
{
    private const string PANEL = 'admin';

    /**
     * @return array<string, array{url: string, nombre: string}> keyed by public section
     */
    public function __invoke(?Authenticatable $usuario): array
    {
        if (! $usuario instanceof User || ! $usuario->canAccessPanel(Filament::getPanel(self::PANEL)) || ! GuardarTextosDePortada::puedeGuardar($usuario)) {
            return [];
        }

        $formulario = EditarContenidoDePortada::getUrl(panel: self::PANEL);

        $atajos = [];

        foreach (FormularioDeContenidoDePortada::bloques() as $clave => $nombre) {
            $atajos[$clave] = ['url' => "{$formulario}#seccion-{$clave}", 'nombre' => $nombre];
        }

        return $atajos;
    }
}
