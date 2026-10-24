<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

final class AccionesDeSolicitudClasificacion
{
    public static function registrarResultado(): Action
    {
        return Action::make('registrarResultado')
            ->label('Registrar resultado')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('success')
            ->authorize('evaluar')
            ->visible(fn (SolicitudClasificacion $record): bool => $record->estado === EstadoSolicitudClasificacion::PendienteEvaluacion)
            ->modalDescription('El ciclo asignado queda habilitado para la matrícula del estudiante en este idioma.')
            ->schema([
                DatePicker::make('fecha_examen')
                    ->label('Fecha del examen')
                    ->default(fn (): string => self::hoy())
                    ->maxDate(fn (): string => self::hoy())
                    ->required(),
                TextInput::make('puntaje')->label('Puntaje')->numeric()->minValue(0)->maxValue(100)->step(0.01)->required(),
                Select::make('ciclo_id')
                    ->label('Ciclo asignado')
                    ->options(fn (SolicitudClasificacion $record): array => self::ciclosDelIdioma($record->idioma_id))
                    ->required(),
                Textarea::make('observaciones')->label('Observaciones')->maxLength(1000)->rows(3),
            ])
            ->action(fn (Action $action, SolicitudClasificacion $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario): SolicitudClasificacion => app(RegistrarResultadoDeClasificacion::class)($record->id, self::resultado($data), $usuario),
                'Resultado registrado',
            ))
            ->after(fn (SolicitudClasificacion $record): SolicitudClasificacion => $record->refresh());
    }

    /**
     * @return array<int, string>
     */
    private static function ciclosDelIdioma(int $idiomaId): array
    {
        $idioma = collect(app(CatalogoDeIdiomas::class)())->first(fn (IdiomaDelCatalogo $idioma): bool => $idioma->id === $idiomaId);

        return $idioma->ciclos ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resultado(array $data): ResultadoDeClasificacion
    {
        return new ResultadoDeClasificacion(
            CarbonImmutable::parse((string) $data['fecha_examen']),
            (string) $data['puntaje'],
            (int) $data['ciclo_id'],
            isset($data['observaciones']) ? (string) $data['observaciones'] : null,
        );
    }

    private static function hoy(): string
    {
        return CarbonImmutable::today('America/Lima')->toDateString();
    }
}
