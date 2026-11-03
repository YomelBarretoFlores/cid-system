<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Habilitaciones\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Actions\AjustarHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

final class AccionesDeHabilitacion
{
    public static function ajustar(): Action
    {
        return self::accion(Action::make('ajustar'))
            ->label('Ajustar ciclo habilitado')
            ->authorize('ajustar', Habilitacion::class);
    }

    public static function ajustarFila(): Action
    {
        return self::accion(Action::make('ajustarFila'))
            ->label('Ajustar')
            ->authorize('ajustar')
            ->fillForm(fn (Habilitacion $record): array => ['persona_id' => $record->persona_id, 'idioma_id' => $record->idioma_id, 'ciclo_id' => $record->ciclo_id]);
    }

    private static function accion(Action $accion): Action
    {
        return $accion
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('warning')
            ->modalHeading('Ajustar el ciclo habilitado')
            ->modalDescription('Para estudiantes que continúan desde el sistema anterior o para corregir una ubicación. El cambio queda en la auditoría con el motivo.')
            ->schema(self::campos())
            ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): Habilitacion => app(AjustarHabilitacion::class)(
                    (int) $data['persona_id'],
                    (int) $data['idioma_id'],
                    (int) $data['ciclo_id'],
                    (string) $data['motivo'],
                    $usuario,
                    confirmarDescenso: (bool) ($data['confirmar_descenso'] ?? false),
                ),
                'Ciclo habilitado ajustado',
            ));
    }

    /**
     * @return list<Field>
     */
    private static function campos(): array
    {
        return [
            Select::make('persona_id')
                ->label('Estudiante')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                ->required(),
            Select::make('idioma_id')
                ->label('Idioma')
                ->options(fn (): array => collect(app(CatalogoDeIdiomas::class)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all())
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('ciclo_id', null))
                ->required(),
            Select::make('ciclo_id')
                ->label('Ciclo habilitado')
                ->options(fn (Get $get): array => self::ciclos($get('idioma_id')))
                ->required(),
            Textarea::make('motivo')->label('Motivo del ajuste')->required()->minLength(5)->maxLength(500)->rows(2),
            Checkbox::make('confirmar_descenso')
                ->label('Confirmo habilitar un ciclo que el estudiante ya aprobó o uno anterior'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function ciclos(mixed $idiomaId): array
    {
        $idioma = collect(app(CatalogoDeIdiomas::class)())->first(fn (IdiomaDelCatalogo $idioma): bool => $idioma->id === (int) $idiomaId);

        return $idioma->ciclos ?? [];
    }
}
