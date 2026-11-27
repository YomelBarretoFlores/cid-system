<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\RegistroDePagoAnterior;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class MatricularEnVentanilla
{
    public function __construct(
        private MatricularEstudiante $matricularEstudiante,
        private RegistroDePagoAnterior $registroDePagoAnterior,
    ) {}

    public function __invoke(int $personaId, int $grupoId, ?int $pagoId, ?PagoAnteriorAlSistema $pagoAnterior, User $actor): Matricula
    {
        throw_unless($actor->can(PermisoMatricula::Gestionar->value), AuthorizationException::class);

        // The new payment row has no contention, so creating it before MatricularEstudiante's person lock keeps its lock order; a refusal rolls it back.
        return Transaccion::ejecutar(fn (): Matricula => ($this->matricularEstudiante)(
            $personaId,
            $grupoId,
            $this->pago($pagoId, $pagoAnterior, $actor),
        ));
    }

    private function pago(?int $pagoId, ?PagoAnteriorAlSistema $pagoAnterior, User $actor): int
    {
        return match (true) {
            $pagoId !== null && ! $pagoAnterior instanceof PagoAnteriorAlSistema => $pagoId,
            $pagoId === null && $pagoAnterior instanceof PagoAnteriorAlSistema => ($this->registroDePagoAnterior)($pagoAnterior, $actor),
            default => throw new ReglaDeNegocioIncumplida('Elige un pago validado del estudiante o registra el pago anterior al sistema.'),
        };
    }
}
