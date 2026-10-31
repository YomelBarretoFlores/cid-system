<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Docente\Pages;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Shared\Ui\Formato;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MisActasFinales extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Actas finales';

    protected static ?string $title = 'Actas finales';

    protected static ?string $slug = 'actas';

    protected ?string $subheading = 'Actas que validaste, con su estado y su PDF.';

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
                Split::make([
                    TextColumn::make('grupo_codigo')
                        ->label('Grupo')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(fn (Acta $record): string => "{$record->grupo_codigo} · {$record->idioma} {$record->ciclo_nombre}")
                        ->description(fn (Acta $record): string => "{$record->periodo} · {$record->getAttribute('aprobados_count')} de {$record->notas_count} aprobados".($record->validada_en === null ? '' : ' · validada el '.Formato::fechaCorta($record->validada_en->setTimezone('America/Lima')))),
                    TextColumn::make('estado')
                        ->label('Estado')
                        ->badge()
                        ->grow(false)
                        ->formatStateUsing(fn (EstadoActa $state): string => $state->etiqueta())
                        ->color(fn (EstadoActa $state): string => $state === EstadoActa::Cerrada ? 'success' : 'warning'),
                ])->from('sm'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver acta')
                    ->extraAttributes(fn (Acta $record): array => ['aria-label' => "Ver acta del grupo {$record->grupo_codigo}"])
                    ->url(fn (Acta $record): string => route('filament.docente.pages.grupos.acta', $record->grupo_id)),
            ])
            ->emptyStateIcon(Heroicon::OutlinedDocumentCheck)
            ->emptyStateHeading('Aún no has cerrado actas finales.')
            ->emptyStateDescription('Ciérralas desde Mis grupos, en la pestaña Acta final de cada grupo.')
            ->paginated(false);
    }
}
