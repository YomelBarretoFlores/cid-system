<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Docente\Pages;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Shared\Ui\Formato;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MisActasFinales extends Page implements HasTable
{
    use InteractsWithTable;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $navigationLabel = 'Actas finales';

    #[\Override]
    protected static ?string $title = 'Actas finales';

    #[\Override]
    protected static ?string $slug = 'actas';

    #[\Override]
    protected ?string $subheading = 'Actas que validaste, con su estado y su PDF.';

    #[\Override]
    protected string $view = 'certificacion::filament.docente.mis-actas-finales';

    public function table(Table $table): Table
    {
        $docenteId = app(AccesoDelDocente::class)->docenteId((int) auth()->id());

        return $table
            ->query(fn (): Builder => Acta::query()
                ->where('docente_id', $docenteId)
                ->withCount(['notas', 'notas as aprobados_count' => fn (Builder $consulta): Builder => $consulta->where('estado_final', EstadoFinal::Aprobado)]))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('validada_en')->orderBy('grupo_codigo'))
            ->columns([
                TextColumn::make('grupo_codigo')
                    ->label('Grupo')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Acta $record): string => "{$record->idioma} {$record->ciclo_nombre} · {$record->getAttribute('aprobados_count')} de {$record->notas_count} aprobados"),
                TextColumn::make('periodo')
                    ->label('Periodo')
                    ->visibleFrom('md'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoActa $state): string => $state->etiqueta())
                    ->color(fn (EstadoActa $state): string => $state === EstadoActa::Cerrada ? 'success' : 'warning'),
                TextColumn::make('validada_en')
                    ->label('Validada')
                    ->formatStateUsing(fn (CarbonInterface $state): string => Formato::fechaCorta($state->setTimezone('America/Lima')))
                    ->placeholder('—')
                    ->visibleFrom('md'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Abrir')
                    ->extraAttributes(fn (Acta $record): array => ['aria-label' => "Abrir el acta del grupo {$record->grupo_codigo}"])
                    ->url(fn (Acta $record): string => route('filament.docente.pages.grupos.acta', $record->grupo_id)),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->extraAttributes(fn (Acta $record): array => ['aria-label' => "PDF del acta del grupo {$record->grupo_codigo}"])
                    ->url(fn (Acta $record): string => route('docente.acta.pdf', $record->grupo_id))
                    ->openUrlInNewTab()
                    ->visible(fn (Acta $record): bool => $record->estado === EstadoActa::Cerrada),
            ])
            ->emptyStateIcon(Heroicon::OutlinedDocumentCheck)
            ->emptyStateHeading('Aún no has cerrado actas finales.')
            ->emptyStateDescription('Ciérralas desde Mis grupos, en la pestaña Acta final de cada grupo.')
            ->paginated(false);
    }
}
