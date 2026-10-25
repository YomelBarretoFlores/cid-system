<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Database\Factories\TramiteFactory;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $codigo
 * @property AccionFinal $accion_final
 * @property ?TipoConcepto $tipo_concepto
 * @property list<array{nombre: string, avance: int, condicion: string}> $etapas
 * @property int $etapa_actual
 * @property EstadoTramite $estado
 * @property array<string, mixed> $datos
 * @property ?CarbonImmutable $finalizado_en
 * @property CarbonImmutable $created_at
 * @property-read TipoTramite $tipoTramite
 */
#[Table('tra_tramites')]
#[Fillable([
    'persona_id', 'tipo_tramite_id', 'tipo_nombre', 'accion_final', 'tipo_concepto', 'etapas', 'etapa_actual', 'estado', 'datos',
    'pago_id', 'observacion', 'documento_id', 'finalizado_en',
])]
#[UseFactory(TramiteFactory::class)]
final class Tramite extends Model
{
    /** @use HasFactory<TramiteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TipoTramite, $this>
     */
    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }

    /**
     * @return HasMany<Movimiento, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('id');
    }

    /**
     * @return HasMany<Notificacion, $this>
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class)->orderBy('id');
    }

    public function avance(): int
    {
        return $this->etapas[$this->etapa_actual]['avance'];
    }

    public function nombreDeEtapa(): string
    {
        return $this->etapas[$this->etapa_actual]['nombre'];
    }

    public function esUltimaEtapa(): bool
    {
        return $this->etapa_actual === count($this->etapas) - 1;
    }

    public function condicionDeLaSiguiente(): ?CondicionDeEtapa
    {
        $siguiente = $this->etapas[$this->etapa_actual + 1] ?? null;

        return $siguiente === null ? null : CondicionDeEtapa::from($siguiente['condicion']);
    }

    protected function casts(): array
    {
        return [
            'accion_final' => AccionFinal::class,
            'tipo_concepto' => TipoConcepto::class,
            'etapas' => 'array',
            'etapa_actual' => 'integer',
            'estado' => EstadoTramite::class,
            'datos' => 'array',
            'finalizado_en' => 'immutable_datetime',
        ];
    }
}
