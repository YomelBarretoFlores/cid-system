<?php

declare(strict_types=1);

namespace App\Shared\Ui\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

// One sidebar entry for every catalog, setting and editable text; each module's screen joins it with $cluster.
final class Configuracion extends Cluster
{
    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    #[\Override]
    protected static ?string $navigationLabel = 'Configuración';

    #[\Override]
    protected static ?string $clusterBreadcrumb = 'Configuración';

    #[\Override]
    protected static ?string $slug = 'configuracion';

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
}
