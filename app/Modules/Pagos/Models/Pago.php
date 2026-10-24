<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Pagos\Database\Factories\PagoFactory;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Policies\PagoPolicy;
use App\Shared\Auth\Models\User;
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
 * @property EstadoPago $estado
 * @property-read Concepto $concepto
 */
#[Table('pag_pagos')]
#[RouteKey('ulid')]
#[Fillable([
    'persona_id', 'concepto_id', 'tarifa_id', 'monto', 'medio_pago', 'numero_operacion', 'fecha_pago',
    'recibo_path', 'estado', 'registrado_por', 'validado_por', 'validado_en', 'motivo_rechazo',
    'anulado_por', 'anulado_en', 'motivo_anulacion',
])]
#[UseFactory(PagoFactory::class)]
#[UsePolicy(PagoPolicy::class)]
final class Pago extends Model
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'estado' => 'pendiente',
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
     * @return BelongsTo<User, $this>
     */
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
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
            'medio_pago' => MedioPago::class,
            'fecha_pago' => 'date',
            'estado' => EstadoPago::class,
            'validado_en' => 'datetime',
            'anulado_en' => 'datetime',
        ];
    }
}
