<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Pagos\Contracts\CatalogoDeConceptos;
use App\Modules\Pagos\Data\ConceptoDelCatalogo;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Actions\GuardarTipoDeTramite;
use App\Modules\Tramites\Data\DatosDeTipoTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Support\ReglaDeReclasificacion;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                        Select::make('concepto_id')
                            ->label('Concepto de pago')
                            ->placeholder('Cualquier concepto de la tasa')
                            ->helperText('Si eliges uno, solo se acepta un pago de ese concepto (y del nivel del trámite cuando su tarifa es por nivel). La tasa toma su tipo.')
                            ->options(fn (): array => self::conceptos()),
                        Select::make('accion_final')
                            ->label('Al finalizar')
                            ->options(array_column(array_map(fn (AccionFinal $accion): array => [$accion->value, $accion->etiqueta()], AccionFinal::cases()), 1, 0))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set, string $operation) => self::camposDeLaAccion($state, $set, $operation)),
                        TextInput::make('meses_sin_estudiar')
                            ->label('Meses sin estudiar')
                            ->helperText('La reclasificación se pide cuando pasaron al menos estos meses desde el fin de la última matrícula en el idioma. Vacío: '.ReglaDeReclasificacion::MESES_POR_DEFECTO.'.')
                            ->numeric()->integer()->minValue(1)->maxValue(24)
                            ->visible(fn (Get $get): bool => $get('accion_final') === AccionFinal::Reclasificacion->value),
                        Toggle::make('activo')->label('Disponible para nuevas solicitudes')->default(true),
                    ]),
                Section::make('Formato único de trámite (FUT)')
                    ->description('Campos que el estudiante llena al solicitar. Su nombre y DNI salen de su cuenta. La acción final exige sus propios campos como obligatorios.')
                    ->columns(2)
                    ->schema([
                        CheckboxList::make('campos')->label('Campos del FUT')->options(CampoFut::opciones())->columns(2),
                        CheckboxList::make('obligatorios')->label('Obligatorios')->helperText('Solo cuentan los campos marcados a la izquierda.')->options(CampoFut::opciones())->columns(2),
                        Select::make('requiere_tipo_previo_id')
                            ->label('Requiere haber finalizado')
                            ->placeholder('Ningún trámite previo')
                            ->helperText('Un trámite finalizado de este tipo, del mismo idioma y nivel.')
                            ->options(fn (?TipoTramite $record): array => TipoTramite::query()->whereKeyNot($record?->id)->orderBy('nombre')->pluck('nombre', 'id')->all()),
                        Select::make('programas')
                            ->label('Programas que pueden solicitarlo')
                            ->placeholder('Todos los programas')
                            ->multiple()
                            ->options(fn (): array => self::programas()),
                        Repeater::make('adjuntos')
                            ->label('Adjuntos')
                            ->helperText('PDF, JPG o PNG de hasta 5 MB cada uno.')
                            ->addActionLabel('Agregar adjunto')
                            ->defaultItems(0)
                            ->maxItems(fn (): int => app(ParametrosDelSistema::class)()->maximoAdjuntosTramite)
                            ->columns(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('nombre')->label('Nombre')->required()->minLength(3)->maxLength(80),
                                Toggle::make('obligatorio')->label('Obligatorio')->default(true),
                            ]),
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
            'concepto_id' => $tipo->concepto_id,
            'accion_final' => $tipo->accion_final->value,
            'campos' => array_column($tipo->campos, 'clave'),
            'obligatorios' => array_column(array_filter($tipo->campos, fn (array $campo): bool => $campo['obligatorio']), 'clave'),
            'requiere_tipo_previo_id' => $tipo->requiere_tipo_previo_id,
            'meses_sin_estudiar' => $tipo->meses_sin_estudiar,
            'programas' => array_map(intval(...), $tipo->programas()->pluck('programa_id')->all()),
            'adjuntos' => $tipo->adjuntos,
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
            conceptoId: self::entero($data['concepto_id'] ?? null),
            campos: self::campos($data),
            tipoPrevioId: self::entero($data['requiere_tipo_previo_id'] ?? null),
            programaIds: self::idsDeProgramas($data['programas'] ?? null),
            adjuntos: self::adjuntos($data['adjuntos'] ?? []),
            mesesSinEstudiar: self::entero($data['meses_sin_estudiar'] ?? null),
        );
    }

    // On create, ticking the final action's own fields saves the admin from an error when nothing else is ticked.
    private static function camposDeLaAccion(?string $accion, Set $set, string $operation): void
    {
        $elegida = AccionFinal::tryFrom((string) $accion);

        if ($operation !== 'create' || ! $elegida instanceof AccionFinal) {
            return;
        }

        $claves = array_map(fn (CampoFut $campo): string => $campo->value, $elegida->camposRequeridos());
        $set('campos', $claves);
        $set('obligatorios', $claves);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return ?list<array{clave: string, obligatorio: bool}> null when the form did not send them, so the final action's fields apply
     */
    private static function campos(array $data): ?array
    {
        if (! array_key_exists('campos', $data)) {
            return null;
        }

        $campos = $data['campos'];
        $marcados = is_array($data['obligatorios'] ?? null) ? $data['obligatorios'] : [];

        return array_values(array_map(
            fn (mixed $clave): array => ['clave' => self::texto($clave), 'obligatorio' => in_array($clave, $marcados, true)],
            is_array($campos) ? $campos : [],
        ));
    }

    /**
     * @return list<array{nombre: string, obligatorio: bool}>
     */
    private static function adjuntos(mixed $adjuntos): array
    {
        return array_values(array_map(fn (mixed $fila): array => [
            'nombre' => self::texto(is_array($fila) ? ($fila['nombre'] ?? null) : null),
            'obligatorio' => is_array($fila) && (bool) ($fila['obligatorio'] ?? false),
        ], is_array($adjuntos) ? $adjuntos : []));
    }

    /**
     * @return list<int>
     */
    private static function idsDeProgramas(mixed $programas): array
    {
        return array_values(array_map(intval(...), array_filter(is_array($programas) ? $programas : [], is_numeric(...))));
    }

    /**
     * @return array<int, string>
     */
    private static function programas(): array
    {
        $activos = array_filter(app(CatalogoDeProgramas::class)(), fn (ProgramaDelCatalogo $programa): bool => $programa->activo);

        return array_column(array_map(fn (ProgramaDelCatalogo $programa): array => [$programa->id, $programa->nombre], $activos), 1, 0);
    }

    /**
     * @return array<int, string>
     */
    private static function conceptos(): array
    {
        $cobrables = array_filter(
            app(CatalogoDeConceptos::class)(),
            fn (ConceptoDelCatalogo $concepto): bool => $concepto->activo && in_array($concepto->tipo, GuardarTipoDeTramite::CONCEPTOS, true),
        );

        return array_column(array_map(fn (ConceptoDelCatalogo $concepto): array => [$concepto->id, "{$concepto->nombre} ({$concepto->tipo->etiqueta()})"], $cobrables), 1, 0);
    }

    private static function entero(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

    private static function texto(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }
}
