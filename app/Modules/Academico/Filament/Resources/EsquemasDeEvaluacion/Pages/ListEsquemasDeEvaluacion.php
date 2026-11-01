<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Pages;

use App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\EsquemaDeEvaluacionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEsquemasDeEvaluacion extends ListRecords
{
    protected static string $resource = EsquemaDeEvaluacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo sistema de evaluación'),
        ];
    }
}
