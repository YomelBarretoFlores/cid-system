<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

enum MedioPago: string
{
    case CajaPresencial = 'caja_presencial';
    case PagaloPe = 'pagalo_pe';
    case BancoNacion = 'banco_nacion';
    case Transferencia = 'transferencia';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::CajaPresencial => 'Caja presencial',
            self::PagaloPe => 'Págalo.pe',
            self::BancoNacion => 'Banco de la Nación',
            self::Transferencia => 'Transferencia bancaria',
            self::Otro => 'Otro',
        };
    }
}
