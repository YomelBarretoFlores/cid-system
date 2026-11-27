<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Illuminate\Http\UploadedFile;

final readonly class PagoAnteriorAlSistema
{
    public function __construct(
        public RegistroDePago $registro,
        public string $motivo,
        public ?UploadedFile $recibo = null,
    ) {}
}
