<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Enums\SeccionDePortada;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

// The header menu and footer of every public page, read once per request from the shared home content and only when a page asks.
final class MarcoDelSitio
{
    private const string PIE_DE_RESPALDO = 'Huaraz, Áncash. Idiomas extranjeros y lenguas originarias en ciclos por nivel.';

    /** @var array<string, mixed>|null */
    private ?array $contenido = null;

    public function __construct(private readonly ContenidoDePortada $portada) {}

    /**
     * @return array{descripcion: string, contacto: array<string, string>, redes: list<array{etiqueta: string, url: string}>}
     */
    public function pie(): array
    {
        /** @var array{descripcion: string, contacto: array<string, string>, redes: list<array{etiqueta: string, url: string}>} $pie */
        $pie = $this->contenido()['pie'];

        return $pie;
    }

    public function secciones(): SeccionesVisibles
    {
        return SeccionesVisibles::de($this->contenido());
    }

    /**
     * @return array<string, mixed>
     */
    private function contenido(): array
    {
        return $this->contenido ??= $this->armar();
    }

    // The privacy and verification pages must still open when the editable texts cannot be read.
    /**
     * @return array<string, mixed>
     */
    private function armar(): array
    {
        try {
            return $this->portada->armar();
        } catch (QueryException $error) {
            Log::warning('No se pudieron leer los textos de la portada; el encabezado y el pie usan los textos de respaldo.', ['causa' => $error::class]);

            return [
                'apartados' => array_fill_keys(array_column(SeccionDePortada::cases(), 'value'), ['visible' => true]),
                'fechas' => [],
                'pie' => ['descripcion' => self::PIE_DE_RESPALDO, 'contacto' => [], 'redes' => []],
            ];
        }
    }
}
