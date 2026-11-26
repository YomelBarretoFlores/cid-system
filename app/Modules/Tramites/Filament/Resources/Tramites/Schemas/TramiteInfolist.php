<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites\Schemas;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\PresentacionDelFut;
use App\Modules\Tramites\Support\PresentacionDelResultado;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TramiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Trámite')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('codigo')->label('Código de seguimiento')->copyable(),
                        TextEntry::make('tipo_nombre')->label('Trámite'),
                        TextEntry::make('persona_id')->label('Estudiante')->columnSpan(2)
                            ->formatStateUsing(fn (int $state): string => app(EtiquetasDePersonas::class)([$state])[$state] ?? '—'),
                        TextEntry::make('estado')->label('Estado')->badge()
                            ->formatStateUsing(fn (EstadoTramite $state): string => $state->etiqueta())
                            ->color(fn (EstadoTramite $state): string => $state->color()),
                        TextEntry::make('etapa_actual')->label('Etapa')->formatStateUsing(fn (Tramite $record): string => "{$record->nombreDeEtapa()} · {$record->avance()} %"),
                        TextEntry::make('created_at')->label('Iniciado')->dateTime('d/m/Y H:i', 'America/Lima'),
                        TextEntry::make('finalizado_en')->label('Finalizado')->dateTime('d/m/Y H:i', 'America/Lima')->placeholder('—'),
                    ]),
                Section::make('Resultado')
                    ->visible(fn (Tramite $record): bool => PresentacionDelResultado::texto($record) !== null)
                    ->schema([TextEntry::make('resultado')->hiddenLabel()->state(fn (Tramite $record): ?string => PresentacionDelResultado::texto($record))]),
                Section::make('Observación')
                    ->visible(fn (Tramite $record): bool => $record->estado === EstadoTramite::Observado)
                    ->schema([TextEntry::make('observacion')->hiddenLabel()]),
                Section::make('Cierre sin atender')
                    ->columns(4)
                    ->visible(fn (Tramite $record): bool => $record->estado === EstadoTramite::Cerrado)
                    ->schema([
                        TextEntry::make('cerrado_en')->label('Cerrado el')->dateTime('d/m/Y H:i', 'America/Lima'),
                        TextEntry::make('motivo_cierre')->label('Motivo')->columnSpan(3),
                    ]),
                Section::make('Solicitud')
                    ->columns(3)
                    ->schema([
                        ...array_map(fn (CampoFut $campo): TextEntry => TextEntry::make("fut_{$campo->value}")
                            ->label($campo->etiqueta())
                            ->state(fn (Tramite $record): ?string => PresentacionDelFut::valor($campo, $record->datos))
                            ->visible(fn (Tramite $record): bool => PresentacionDelFut::valor($campo, $record->datos) !== null)
                            ->columnSpan(in_array($campo, [CampoFut::Exposicion, CampoFut::Motivo], true) ? 'full' : 1), CampoFut::cases()),
                        TextEntry::make('datos.comentario')->label('Comentario del estudiante')->visible(fn (Tramite $record): bool => isset($record->datos['comentario']))->columnSpanFull(),
                    ]),
                Section::make('Adjuntos')
                    ->visible(fn (Tramite $record): bool => $record->adjuntos()->exists())
                    ->schema([ViewEntry::make('adjuntos')->hiddenLabel()->view('tramites::filament.adjuntos-del-tramite')]),
                Section::make('Tasa')
                    ->columns(4)
                    ->visible(fn (Tramite $record): bool => $record->tipo_concepto !== null)
                    ->schema([
                        TextEntry::make('tipo_concepto')->label('Concepto')->formatStateUsing(fn (Tramite $record): string => $record->tipo_concepto?->etiqueta() ?? '—'),
                        TextEntry::make('pago_id')->label('Pago aplicado')->placeholder('Aún sin pago validado')
                            ->formatStateUsing(fn (int $state): string => self::pago($state)),
                    ]),
                Section::make('Documento emitido')
                    ->visible(fn (Tramite $record): bool => $record->documento_id !== null)
                    ->schema([
                        TextEntry::make('documento_id')->hiddenLabel()
                            ->formatStateUsing(fn (): string => 'Descargar el PDF')
                            ->url(fn (Tramite $record): string => route('panel.documentos.pdf', $record->documento_id), shouldOpenInNewTab: true),
                    ]),
                Section::make('Historial')
                    ->schema([ViewEntry::make('movimientos')->hiddenLabel()->view('tramites::filament.historial-del-tramite')]),
                Section::make('Correos enviados')
                    ->description('Un correo por cada cambio de estado o de etapa, con su estado de entrega.')
                    ->schema([ViewEntry::make('notificaciones')->hiddenLabel()->view('tramites::filament.notificaciones-del-tramite')]),
            ]);
    }

    private static function pago(int $pagoId): string
    {
        $pago = app(ConsultaDePago::class)($pagoId);

        return "{$pago->concepto} · operación {$pago->numeroOperacion} · S/ {$pago->monto} · {$pago->estado->etiqueta()}";
    }
}
