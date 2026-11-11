<?php

declare(strict_types=1);

namespace App\Shared\Audit\Filament\Resources\Auditoria\Pages;

use App\Shared\Audit\Filament\Resources\Auditoria\RegistroDeAuditoriaResource;
use Filament\Resources\Pages\ListRecords;

final class ListRegistrosDeAuditoria extends ListRecords
{
    #[\Override]
    protected static string $resource = RegistroDeAuditoriaResource::class;
}
