<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Support\AltaDePago;
use App\Shared\Auth\Models\User;
use Illuminate\Http\UploadedFile;

final readonly class RegistrarPago
{
    public function __construct(private AltaDePago $altaDePago) {}

    public function __invoke(RegistroDePago $registro, UploadedFile $recibo, ?User $registradoPor = null): Pago
    {
        return $this->altaDePago->registrar($registro, $recibo, ['registrado_por' => $registradoPor?->id]);
    }
}
