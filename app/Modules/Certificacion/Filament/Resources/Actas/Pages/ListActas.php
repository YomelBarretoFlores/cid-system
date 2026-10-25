<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Pages;

use App\Modules\Certificacion\Filament\Resources\Actas\ActaResource;
use Filament\Resources\Pages\ListRecords;

final class ListActas extends ListRecords
{
    protected static string $resource = ActaResource::class;

    protected ?string $subheading = 'Actas validadas por los docentes; se corrigen solo con una rectificación autorizada.';
}
