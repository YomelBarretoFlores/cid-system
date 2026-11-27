<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Support\TarifaAplicable;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

// Payment fields shared by the payment registration and the office enrollment; they read the payer from `persona_id`.
final class CamposDePago
{
    public static function concepto(?TipoConcepto $tipo = null): Select
    {
        return Select::make('concepto_id')
            ->label('Concepto TUPA/TUSNE')
            ->options(fn (): array => Concepto::query()->where('activo', true)->when($tipo, fn ($query, TipoConcepto $t) => $query->where('tipo', $t))->orderBy('nombre')->pluck('nombre', 'id')->all())
            ->live()
            ->required();
    }

    public static function nivel(): Select
    {
        return Select::make('nivel')
            ->label('Nivel pagado')
            ->options(array_column(array_map(fn (NivelCurricular $nivel): array => [$nivel->value, $nivel->etiqueta()], NivelCurricular::cases()), 1, 0))
            ->visible(fn (Get $get): bool => self::criterios($get)['nivel'])
            ->required(fn (Get $get): bool => self::criterios($get)['nivel']);
    }

    public static function programa(): Select
    {
        return Select::make('programa_id')
            ->label('Programa')
            ->placeholder('Ninguno en particular')
            ->helperText('Solo si el pago es la tarifa de un programa, por ejemplo Posgrado.')
            ->options(fn (): array => self::programasActivos())
            ->visible(fn (Get $get): bool => self::criterios($get)['programa']);
    }

    public static function medioDePago(): Select
    {
        return Select::make('medio_pago_id')
            ->label('Medio de pago')
            ->options(fn (): array => MedioDePago::query()->activos()->pluck('nombre', 'id')->all())
            ->default(fn (): ?int => MedioDePago::query()->activos()->value('id'))
            ->required();
    }

    public static function numeroOperacion(): TextInput
    {
        return TextInput::make('numero_operacion')->label('N.° de recibo u operación')->required()->maxLength(40);
    }

    public static function fechaPago(): DatePicker
    {
        return DatePicker::make('fecha_pago')
            ->label('Fecha de pago')
            ->live()
            ->required()
            ->maxDate(fn (): string => CarbonImmutable::today('America/Lima')->toDateString())
            ->default(fn (): string => CarbonImmutable::today('America/Lima')->toDateString());
    }

    public static function recibo(): FileUpload
    {
        return FileUpload::make('recibo')
            ->label('Recibo escaneado (JPG, PNG o PDF)')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
            ->maxSize(fn (): int => app(ParametrosDelSistema::class)()->reciboMaximoKb())
            ->storeFiles(false)
            ->columnSpanFull();
    }

    /**
     * @param  array<string, mixed>  $datos  the validated form state
     */
    public static function registro(array $datos): RegistroDePago
    {
        return new RegistroDePago(
            personaId: self::entero($datos['persona_id'] ?? null) ?? 0,
            conceptoId: self::entero($datos['concepto_id'] ?? null) ?? 0,
            medioPagoId: self::entero($datos['medio_pago_id'] ?? null) ?? 0,
            numeroOperacion: self::texto($datos['numero_operacion'] ?? null),
            fechaPago: CarbonImmutable::parse(self::texto($datos['fecha_pago'] ?? null)),
            monto: self::texto($datos['monto'] ?? null),
            nivel: NivelCurricular::tryFrom(self::texto($datos['nivel'] ?? null)),
            programaId: self::entero($datos['programa_id'] ?? null),
        );
    }

    /**
     * @return array{nivel: bool, programa: bool}
     */
    private static function criterios(Get $get): array
    {
        $concepto = $get('concepto_id');
        $persona = $get('persona_id');
        $fecha = $get('fecha_pago');

        if (! is_numeric($concepto) || ! is_numeric($persona)) {
            return ['nivel' => false, 'programa' => false];
        }

        $dia = is_string($fecha) && $fecha !== '' ? CarbonImmutable::parse($fecha) : CarbonImmutable::today('America/Lima');

        return TarifaAplicable::criterios((int) $concepto, app(ConsultaDeVinculo::class)((int) $persona), $dia);
    }

    /**
     * @return array<int, string>
     */
    private static function programasActivos(): array
    {
        $activos = array_filter(app(CatalogoDeProgramas::class)(), fn (ProgramaDelCatalogo $programa): bool => $programa->activo);

        return array_column(array_map(fn (ProgramaDelCatalogo $programa): array => [$programa->id, $programa->nombre], $activos), 1, 0);
    }

    private static function entero(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

    private static function texto(mixed $valor): string
    {
        return is_string($valor) || is_int($valor) || is_float($valor) ? (string) $valor : '';
    }
}
