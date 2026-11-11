<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewGrupo extends ViewRecord
{
    #[\Override]
    protected static string $resource = GrupoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Coordination prints the grades registered so far; the acta itself belongs to Certificacion.
            Action::make('descargarPreActa')
                ->label('Pre acta (PDF)')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false)
                ->url(fn (): string => route('panel.grupos.preacta.pdf', $this->getRecord()->getKey()))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
