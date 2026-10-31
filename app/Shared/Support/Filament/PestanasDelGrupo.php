<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;

// The teacher pages of one group (attendance, pre-acta, final acta, syllabus) share a tab bar; the first three keep the URLs the portal always had.
trait PestanasDelGrupo
{
    private const array PESTANAS = ['asistencia' => 'Asistencia', 'notas' => 'Pre-acta', 'acta' => 'Acta final', 'silabo' => 'Sílabo'];

    abstract protected function pestana(): string;

    abstract protected function grupoDeLaPestana(): int;

    // The portal routes only matched numeric groups; the panel slugs match anything, so a typed URL like /docente/grupos/abc stays a 404.
    protected static function grupoDeLaRuta(string $grupo): int
    {
        abort_unless(ctype_digit($grupo), 404);

        return (int) $grupo;
    }

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
