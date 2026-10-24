<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use App\Modules\Matricula\Database\Factories\SolicitudClasificacionFactory;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Policies\SolicitudClasificacionPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EstadoSolicitudClasificacion $estado
 * @property CarbonImmutable $vence_en
 * @property CarbonImmutable|null $fecha_examen
 * @property CarbonImmutable $created_at
 */
#[Table('mat_solicitudes_clasificacion')]
#[RouteKey('ulid')]
#[Fillable([
    'persona_id', 'idioma_id', 'pago_id', 'estado', 'vence_en', 'fecha_examen', 'puntaje',
    'ciclo_asignado_id', 'evaluado_por', 'evaluado_en', 'observaciones',
])]
#[UseFactory(SolicitudClasificacionFactory::class)]
#[UsePolicy(SolicitudClasificacionPolicy::class)]
final class SolicitudClasificacion extends Model
{
    /** @use HasFactory<SolicitudClasificacionFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'estado' => 'pendiente_evaluacion',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    protected function casts(): array
    {
        return [
            'estado' => EstadoSolicitudClasificacion::class,
            'vence_en' => 'date',
            'fecha_examen' => 'date',
            'puntaje' => 'decimal:2',
            'evaluado_en' => 'datetime',
        ];
    }
}
