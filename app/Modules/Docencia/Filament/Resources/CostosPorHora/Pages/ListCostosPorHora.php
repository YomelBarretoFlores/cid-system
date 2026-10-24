<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CostosPorHora\Pages;

use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Filament\Resources\CostosPorHora\CostoPorHoraResource;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListCostosPorHora extends ListRecords
{
    protected static string $resource = CostoPorHoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrarCosto')
                ->label('Nuevo costo por hora')
                ->icon(Heroicon::OutlinedPlus)
                ->authorize('create')
                ->modalDescription('Si la categoría tiene un costo abierto, se cierra el día anterior al inicio del nuevo.')
                ->schema([
                    Select::make('condicion_laboral')
                        ->label('Categoría (condición laboral)')
                        ->options(collect(CondicionLaboral::cases())->mapWithKeys(fn (CondicionLaboral $condicion): array => [$condicion->value => $condicion->etiqueta()])->all())
                        ->required(),
                    TextInput::make('costo_hora')->label('Costo por hora')->prefix('S/')->numeric()->minValue(0.01)->maxValue(99999999.99)->required(),
                    DatePicker::make('vigente_desde')->label('Vigente desde')->required(),
                    DatePicker::make('vigente_hasta')->label('Vigente hasta')->afterOrEqual('vigente_desde'),
                ])
                ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                    $action,
                    fn (): CostoPorHora => app(RegistrarCostoPorHora::class)(
                        CondicionLaboral::from($data['condicion_laboral']),
                        (string) $data['costo_hora'],
                        CarbonImmutable::parse($data['vigente_desde']),
                        filled($data['vigente_hasta'] ?? null) ? CarbonImmutable::parse($data['vigente_hasta']) : null,
                        $this->usuario(),
                    ),
                    'Costo por hora registrado',
                )),
        ];
    }

    private function usuario(): User
    {
        /** @var User */
        return auth()->user();
    }
}
