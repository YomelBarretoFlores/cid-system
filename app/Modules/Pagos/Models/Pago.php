<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Pagos\Database\Factories\PagoFactory;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Policies\PagoPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $monto
 * @property CarbonImmutable $fecha_pago
 * @property EstadoPago $estado
 * @property int $medio_pago_id
 * @property-read MedioDePago $medioDePago
 * @property ?int $registrado_por
 * @property bool $exige_boleta_original
 * @property ?int $boleta_original_verificada_por
 * @property ?CarbonImmutable $boleta_original_verificada_en
 * @property ?string $justificacion_monto
 * @property ?string $recibo_sha256
 * @property ?string $recibo_path
 * @property bool $anterior_al_sistema
 * @property ?string $motivo_anterior
 * @property-read ?Tarifa $tarifa
 * @property-read Concepto $concepto
 */
#[Table('pag_pagos')]
#[RouteKey('ulid')]
#[Fillable([
    'persona_id', 'concepto_id', 'tarifa_id', 'monto', 'medio_pago_id', 'numero_operacion', 'fecha_pago',
    'recibo_path', 'estado', 'registrado_por', 'validado_por', 'validado_en', 'motivo_rechazo',
    'anulado_por', 'anulado_en', 'motivo_anulacion', 'boleta_original_verificada_por', 'boleta_original_verificada_en', 'justificacion_monto', 'recibo_sha256',
    'exige_boleta_original', 'anterior_al_sistema', 'motivo_anterior',
])]
#[UseFactory(PagoFactory::class)]
#[UsePolicy(PagoPolicy::class)]
final class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory, HasUlids;

    #[\Override]
    protected $attributes = [
        'estado' => 'pendiente',
        'anterior_al_sistema' => false,
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return BelongsTo<Concepto, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }

    /**
     * @return BelongsTo<Tarifa, $this>
     */
    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class);
    }

    /**
     * @return BelongsTo<MedioDePago, $this>
     */
    public function medioDePago(): BelongsTo
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verificadorDeBoleta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boleta_original_verificada_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function anulador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    /**
     * @return Attribute<string, string>
     */
    protected function numeroOperacion(): Attribute
    {
        return Attribute::set(fn (string $value): string => mb_strtoupper(trim($value)));
    }

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
            'estado' => EstadoPago::class,
            'validado_en' => 'datetime',
            'anulado_en' => 'datetime',
            'exige_boleta_original' => 'boolean',
            'anterior_al_sistema' => 'boolean',
            'boleta_original_verificada_en' => 'immutable_datetime',
        ];
    }
}
