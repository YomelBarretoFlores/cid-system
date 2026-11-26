<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Pagos\Support\TarifaAplicable;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use finfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class RegistrarPago
{
    private const string DISCO = 'local';

    private const array EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private ConsultaDeVinculo $vinculo) {}

    public function __invoke(RegistroDePago $registro, UploadedFile $recibo, ?User $registradoPor = null): Pago
    {
        $extension = $this->extensionDelRecibo($recibo);
        $huella = (string) hash_file('sha256', $recibo->getRealPath());
        // Fail fast before storing the receipt; the binding read happens under lock below.
        $this->asegurarMedioDisponible($registro->medioPagoId);
        $this->tarifaAplicable($registro);
        $ruta = $recibo->storeAs("recibos/{$registro->fechaPago->format('Y')}", Str::ulid().".{$extension}", self::DISCO);

        try {
            // The savepoint keeps a caller's transaction usable after a duplicate number.
            return Transaccion::ejecutar(fn (): Pago => Pago::query()->create([
                'persona_id' => $registro->personaId,
                'concepto_id' => $registro->conceptoId,
                // Locks concept, then fee (the order RegistrarTarifa uses), waiting for any fee change in progress.
                'tarifa_id' => $this->tarifaAplicable($registro, bloquear: true)->id,
                'monto' => $registro->monto,
                'medio_pago_id' => $registro->medioPagoId,
                'numero_operacion' => $registro->numeroOperacion,
                'fecha_pago' => $registro->fechaPago,
                'recibo_path' => $ruta,
                'recibo_sha256' => $huella,
                'registrado_por' => $registradoPor?->id,
            ]));
        } catch (Throwable $error) {
            Storage::disk(self::DISCO)->delete((string) $ruta);

            throw $error instanceof UniqueConstraintViolationException ? $this->duplicado($error) : $error;
        }
    }

    private function duplicado(UniqueConstraintViolationException $error): ReglaDeNegocioIncumplida
    {
        return RestriccionUnica::es($error, 'pag_pagos_recibo_sha256_unique')
            ? new ReglaDeNegocioIncumplida('Esta boleta ya fue registrada en otro pago.', previous: $error)
            : new ReglaDeNegocioIncumplida('Ese número de operación ya fue registrado con este medio de pago.', previous: $error);
    }

    private function asegurarMedioDisponible(int $medioPagoId): void
    {
        if (! MedioDePago::query()->whereKey($medioPagoId)->where('activo', true)->exists()) {
            throw new ReglaDeNegocioIncumplida('El medio de pago no está disponible.');
        }
    }

    private function extensionDelRecibo(UploadedFile $recibo): string
    {
        $tipo = new finfo(FILEINFO_MIME_TYPE)->file($recibo->getRealPath());

        return self::EXTENSIONES[$tipo]
            ?? throw new ReglaDeNegocioIncumplida('El recibo debe ser una imagen JPG o PNG, o un PDF.');
    }

    private function tarifaAplicable(RegistroDePago $registro, bool $bloquear = false): Tarifa
    {
        $concepto = Concepto::query()
            ->whereKey($registro->conceptoId)
            ->where('activo', true)
            ->when($bloquear, fn (Builder $query): Builder => $query->sharedLock())
            ->first()
            ?? throw new ReglaDeNegocioIncumplida('El concepto de pago no está disponible.');

        $vinculo = ($this->vinculo)($registro->personaId);

        return TarifaAplicable::buscar($concepto->id, $vinculo, $registro->fechaPago, $registro->nivel, $registro->programaId, $bloquear)
            ?? throw new ReglaDeNegocioIncumplida($this->sinTarifa($concepto->id, $vinculo, $registro));
    }

    private function sinTarifa(int $conceptoId, string $vinculo, RegistroDePago $registro): string
    {
        $criterios = TarifaAplicable::criterios($conceptoId, $vinculo, $registro->fechaPago);

        return match (true) {
            $criterios['nivel'] && ! $registro->nivel instanceof NivelCurricular => 'Indica el nivel al que corresponde el pago.',
            $criterios['programa'] && $registro->programaId === null => 'Indica el programa al que corresponde el pago.',
            default => 'No hay una tarifa vigente para este concepto en la fecha de pago.',
        };
    }
}
