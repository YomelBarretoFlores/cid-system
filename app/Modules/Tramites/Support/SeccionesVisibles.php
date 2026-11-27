<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Enums\SeccionDePortada;

// Which home sections are shown and linked from the menus, so no page links to a hidden one.
final readonly class SeccionesVisibles
{
    private const array EN_LA_CABECERA = ['idiomas', 'requisitos', 'oferta', 'costos', 'preguntas'];

    /**
     * @param  array<string, array{visible: bool}>  $apartados  keyed by SeccionDePortada value
     */
    public function __construct(private array $apartados, private bool $hayFechas) {}

    /**
     * @param  array<string, mixed>  $contenido  what ContenidoDePortada assembles
     */
    public static function de(array $contenido): self
    {
        /** @var array<string, array{visible: bool}> $apartados */
        $apartados = $contenido['apartados'];

        return new self($apartados, $contenido['fechas'] !== []);
    }

    public function visible(string $clave): bool
    {
        return $this->apartados[$clave]['visible'] && ($clave !== SeccionDePortada::Fechas->value || $this->hayFechas);
    }

    /**
     * @return array<string, string> anchor => menu label
     */
    public function enlaces(): array
    {
        $enlaces = [];

        foreach (SeccionDePortada::cases() as $seccion) {
            if ($seccion->enlace() !== null && $this->visible($seccion->value)) {
                $enlaces[$seccion->value] = $seccion->enlace();
            }
        }

        return $enlaces;
    }

    /**
     * @return array<string, string>
     */
    public function enlacesDeCabecera(): array
    {
        return array_intersect_key($this->enlaces(), array_flip(self::EN_LA_CABECERA));
    }
}
