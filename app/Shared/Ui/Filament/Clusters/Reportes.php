<?php

declare(strict_types=1);

namespace App\Shared\Ui\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

// One sidebar entry for the reports of every module, shown as tabs.
final class Reportes extends Cluster
{
    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    #[\Override]
    protected static ?string $navigationLabel = 'Reportes';

    #[\Override]
    protected static ?string $clusterBreadcrumb = 'Reportes';

    #[\Override]
    protected static ?string $slug = 'reportes';

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    #[\Override]
    protected static ?int $navigationSort = 0;

    #[\Override]
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
