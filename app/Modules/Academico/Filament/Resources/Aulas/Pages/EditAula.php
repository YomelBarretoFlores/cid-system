<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Pages;

use App\Modules\Academico\Filament\Resources\Aulas\AulaResource;
use Filament\Resources\Pages\EditRecord;

final class EditAula extends EditRecord
{
    protected static string $resource = AulaResource::class;
}
