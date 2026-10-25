<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Data\DatosDeTipoTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\TipoTramite;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TipoTramiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Trámite')
                    ->columns(2)
                    ->schema([
                        TextInput::make('codigo')->label('Código')->required()->maxLength(30)->alphaDash(),
                        TextInput::make('nombre')->label('Nombre')->required()->maxLength(120),
                        Textarea::make('requisitos')->label('Requisitos')->helperText('El estudiante los ve antes de iniciar el trámite.')->maxLength(1000)->rows(3)->columnSpanFull(),
                        Select::make('tipo_concepto')
                            ->label('Tasa')
                            ->placeholder('Sin tasa')
                            ->helperText('El trámite espera a que Pagos valide un pago de este concepto.')
                            ->options(array_column(array_map(fn (TipoConcepto $tipo): array => [$tipo->value, $tipo->etiqueta()], GuardarTipoDeTramite::CONCEPTOS), 1, 0)),
                        Select::make('accion_final')
                            ->label('Al finalizar')
                            ->options(array_column(array_map(fn (AccionFinal $accion): array => [$accion->value, $accion->etiqueta()], AccionFinal::cases()), 1, 0))
                            ->required(),
                        Toggle::make('activo')->label('Disponible para nuevas solicitudes')->default(true),
                    ]),
                Section::make('Etapas')
                    ->description('La primera es la solicitud recibida (0 %) y la última llega al 100 % por acción del personal. Con tasa, la segunda etapa es la del pago validado. Los trámites ya iniciados conservan sus etapas.')
                    ->schema([
                        Repeater::make('etapas')
                            ->hiddenLabel()
                            ->addActionLabel('Agregar etapa')
                            ->reorderable()
                            ->minItems(2)
                            ->columns(3)
                            ->schema([
                                TextInput::make('nombre')->label('Nombre')->required()->maxLength(80),
                                TextInput::make('avance')->label('Avance')->numeric()->integer()->minValue(0)->maxValue(100)->suffix('%')->required(),
                                Select::make('condicion')
                                    ->label('Se alcanza')
                                    ->options(array_column(array_map(fn (CondicionDeEtapa $condicion): array => [$condicion->value, $condicion->etiqueta()], CondicionDeEtapa::cases()), 1, 0))
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function estado(TipoTramite $tipo): array
    {
        return [
            ...$tipo->only(['codigo', 'nombre', 'requisitos', 'activo']),
            'tipo_concepto' => $tipo->tipo_concepto?->value,
            'accion_final' => $tipo->accion_final->value,
            'etapas' => $tipo->etapas->map(fn (Etapa $etapa): array => ['nombre' => $etapa->nombre, 'avance' => $etapa->avance, 'condicion' => $etapa->condicion->value])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeTipoTramite
    {
        $etapas = is_array($data['etapas'] ?? null) ? array_values($data['etapas']) : [];

        return new DatosDeTipoTramite(
            codigo: self::texto($data['codigo'] ?? null),
            nombre: self::texto($data['nombre'] ?? null),
            requisitos: self::texto($data['requisitos'] ?? null),
            tipoConcepto: TipoConcepto::tryFrom(self::texto($data['tipo_concepto'] ?? null)),
            accionFinal: AccionFinal::tryFrom(self::texto($data['accion_final'] ?? null)) ?? AccionFinal::Ninguna,
            activo: (bool) ($data['activo'] ?? false),
            etapas: array_map(fn (mixed $fila): array => [
                'nombre' => self::texto(is_array($fila) ? ($fila['nombre'] ?? null) : null),
                'avance' => (int) (is_array($fila) && is_numeric($fila['avance'] ?? null) ? $fila['avance'] : -1),
                'condicion' => CondicionDeEtapa::tryFrom(self::texto(is_array($fila) ? ($fila['condicion'] ?? null) : null)) ?? CondicionDeEtapa::AccionDelPersonal,
            ], $etapas),
        );
    }

    private static function texto(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }
}
