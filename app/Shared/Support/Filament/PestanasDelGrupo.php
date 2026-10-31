<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;

// The three teacher pages of one group (attendance, pre-acta, final acta) share a tab bar; the URLs are the ones the portal always had.
trait PestanasDelGrupo
{
    private const array PESTANAS = ['asistencia' => 'Asistencia', 'notas' => 'Pre-acta', 'acta' => 'Acta final'];

    abstract protected function pestana(): string;

    abstract protected function grupoDeLaPestana(): int;

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * @return list<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        return array_map(
            fn (string $pestana, string $etiqueta): NavigationItem => NavigationItem::make($etiqueta)
                ->url(url("/docente/grupos/{$this->grupoDeLaPestana()}/{$pestana}"))
                ->isActiveWhen(fn (): bool => $pestana === $this->pestana()),
            array_keys(self::PESTANAS),
            self::PESTANAS,
        );
    }
}
