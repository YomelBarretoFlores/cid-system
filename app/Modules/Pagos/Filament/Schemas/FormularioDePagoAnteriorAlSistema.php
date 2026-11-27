<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Schemas;

use App\Modules\Pagos\Contracts\FormularioDePagoAnterior;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Modules\Pagos\Enums\TipoConcepto;
use Filament\Forms\Components\Textarea;
use Illuminate\Http\UploadedFile;

final readonly class FormularioDePagoAnteriorAlSistema implements FormularioDePagoAnterior
{
    public function campos(): array
    {
        return [
            CamposDePago::concepto(TipoConcepto::Matricula),
            CamposDePago::nivel(),
            CamposDePago::programa(),
            CamposDePago::medioDePago(),
            CamposDePago::numeroOperacion(),
            CamposDePago::fechaPago(),
            CampoDeMonto::make(),
            Textarea::make('motivo_anterior')
                ->label('Motivo')
                ->helperText('Por ejemplo: «Pago de noviembre de 2026, anterior al sistema».')
                ->required()
                ->maxLength(500)
                ->rows(2)
                ->columnSpanFull(),
            CamposDePago::recibo()->helperText('Opcional: adjúntalo si el estudiante lo tiene.'),
        ];
    }

    public function pago(array $datos): PagoAnteriorAlSistema
    {
        $recibo = $datos['recibo'] ?? null;

        return new PagoAnteriorAlSistema(
            CamposDePago::registro($datos),
            (string) $datos['motivo_anterior'],
            $recibo instanceof UploadedFile ? $recibo : null,
        );
    }
}
