<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Schemas;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Support\OpcionesDeMatricula;
use App\Modules\Pagos\Contracts\FormularioDePagoAnterior;
use App\Modules\Pagos\Contracts\PagosAnterioresPermitidos;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Personas\Contracts\FormularioDePersona;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

final class MatriculaEnVentanillaForm
{
    public const string PAGO_VALIDADO = 'validado';

    public const string PAGO_ANTERIOR = 'anterior';

    /**
     * @return list<Component>
     */
    public static function componentes(): array
    {
        return [
            Section::make('Estudiante')
                ->description('Búscalo por su documento de identidad. Si aún no está registrado, completa sus datos aquí mismo.')
                ->schema([...app(FormularioDePersona::class)->campos(conId: true), Hidden::make('persona_id')]),
            Section::make('Matrícula')
                ->schema(self::matricula()),
        ];
    }

    /**
     * @return list<Component>
     */
    private static function matricula(): array
    {
        return [
            Select::make('grupo_id')
                ->label('Grupo')
                ->helperText('Grupos con matrícula abierta del ciclo para el que está habilitado.')
                ->options(fn (Get $get): array => self::grupos($get))
                ->required(),
            Radio::make('modo_de_pago')
                ->label('Pago')
                ->options(fn (): array => self::modosDePago())
                ->default(self::PAGO_VALIDADO)
                ->live()
                ->required(),
            Select::make('pago_id')
                ->label('Pago validado del estudiante')
                ->helperText('Pagos de matrícula validados que aún no se usaron en otra matrícula.')
                ->options(fn (Get $get): array => self::pagosDisponibles($get))
                ->visible(fn (Get $get): bool => $get('modo_de_pago') === self::PAGO_VALIDADO && self::pagosDisponibles($get) !== [])
                ->required(),
            Callout::make('Sin pagos de matrícula validados')
                ->key('sin-pagos-validados')
                ->description('Registra primero su pago en «Pagos». Cuando otra persona lo valide, podrás matricularlo con ese pago.')
                ->warning()
                ->visible(fn (Get $get): bool => $get('modo_de_pago') === self::PAGO_VALIDADO && self::pagosDisponibles($get) === []),
            Grid::make(2)
                ->schema(fn (): array => app(FormularioDePagoAnterior::class)->campos())
                ->visible(fn (Get $get): bool => $get('modo_de_pago') === self::PAGO_ANTERIOR && self::ofrecePagoAnterior()),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function pagoAnterior(array $datos): ?PagoAnteriorAlSistema
    {
        return ($datos['modo_de_pago'] ?? null) === self::PAGO_ANTERIOR ? app(FormularioDePagoAnterior::class)->pago($datos) : null;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function pagoId(array $datos): ?int
    {
        return ($datos['modo_de_pago'] ?? null) === self::PAGO_VALIDADO && is_numeric($datos['pago_id'] ?? null) ? (int) $datos['pago_id'] : null;
    }

    /**
     * @return array<string, string>
     */
    private static function modosDePago(): array
    {
        $modos = [self::PAGO_VALIDADO => 'Pago validado del estudiante'];

        return self::ofrecePagoAnterior() ? [...$modos, self::PAGO_ANTERIOR => 'Pago anterior al sistema'] : $modos;
    }

    /**
     * @return array<int, string>
     */
    private static function grupos(Get $get): array
    {
        $personaId = $get('persona_id');
        $persona = $get(FormularioDePersona::CAMPO);
        $nueva = ! is_numeric($personaId) && is_array($persona) && is_string($persona['numero_documento'] ?? null) && trim($persona['numero_documento']) !== '';

        if (! is_numeric($personaId) && ! $nueva) {
            return [];
        }

        $grupos = app(OpcionesDeMatricula::class)->oferta(is_numeric($personaId) ? (int) $personaId : null);

        return array_column(array_map(fn (FichaDeGrupo $grupo): array => [
            $grupo->id,
            "{$grupo->codigo} · {$grupo->idioma} {$grupo->cicloNumero} · {$grupo->periodo} · {$grupo->vacantesDisponibles} vacantes",
        ], $grupos), 1, 0);
    }

    /**
     * @return array<int, string>
     */
    private static function pagosDisponibles(Get $get): array
    {
        $personaId = $get('persona_id');

        if (! is_numeric($personaId)) {
            return [];
        }

        $pagos = app(OpcionesDeMatricula::class)->pagosDisponibles((int) $personaId);

        return array_column(array_map(fn (PagoValidado $pago): array => [
            $pago->id,
            "{$pago->concepto} · N.° {$pago->numeroOperacion} · S/ {$pago->monto} · {$pago->fechaPago->format('d/m/Y')}",
        ], $pagos), 1, 0);
    }

    // Recording a validated payment also needs the permission to register payments.
    private static function ofrecePagoAnterior(): bool
    {
        return app(PagosAnterioresPermitidos::class)() && auth()->user()?->can(PermisoPago::Registrar->value) === true;
    }
}
