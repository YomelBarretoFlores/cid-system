<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Pages;

use App\Modules\Pagos\Actions\GuardarConfiguracionDePagos;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Configuracion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Ui\Filament\Clusters\Configuracion as ClusterDeConfiguracion;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class EditarConfiguracionDePagos extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    #[\Override]
    protected static ?string $cluster = ClusterDeConfiguracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Pagos';

    #[\Override]
    protected static ?int $navigationSort = 11;

    #[\Override]
    protected static ?string $navigationLabel = 'Configuración de pagos';

    #[\Override]
    protected static ?string $title = 'Configuración de pagos';

    #[\Override]
    protected static ?string $slug = 'configuracion-de-pagos';

    #[\Override]
    protected string $view = 'pagos::filament.configuracion-de-pagos';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoPago::Configurar->value) ?? false;
    }

    public function mount(): void
    {
        $configuracion = Configuracion::query()->find(Configuracion::FILA) ?? new Configuracion;

        $this->form->fill([
            'recargo_matricula_extemporanea' => $configuracion->recargo_matricula_extemporanea,
            'permite_pagos_anteriores_al_sistema' => $configuracion->permite_pagos_anteriores_al_sistema,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Matrícula')->schema([
                    TextInput::make('recargo_matricula_extemporanea')
                        ->label('Recargo por matrícula extemporánea (%)')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Se suma a la tarifa de matrícula pagada en el plazo de matrícula extemporánea del periodo. 0 lo desactiva.'),
                    Toggle::make('permite_pagos_anteriores_al_sistema')
                        ->label('Permitir pagos anteriores al sistema')
                        ->helperText('Permite matricular en ventanilla con un pago hecho fuera del sistema, sin el archivo del recibo y con un motivo.'),
                ]),
            ]);
    }

    public function guardar(GuardarConfiguracionDePagos $guardar): void
    {
        $datos = $this->form->getState();

        try {
            $guardar($datos, AccionDeNegocio::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);

            return;
        }

        Notification::make()->success()->title('Configuración de pagos guardada')->send();
    }
}
