<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Tramites\Enums\EstadoNotificacion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EstadoNotificacion $estado
 * @property ?CarbonImmutable $enviado_en
 * @property CarbonImmutable $created_at
 */
#[Table('tra_notificaciones')]
#[Fillable(['tramite_id', 'destinatario', 'asunto', 'estado', 'intentos', 'enviado_en', 'error'])]
final class Notificacion extends Model
{
    /**
     * @return BelongsTo<Tramite, $this>
     */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    protected function casts(): array
    {
        return [
            'estado' => EstadoNotificacion::class,
            'intentos' => 'integer',
            'enviado_en' => 'immutable_datetime',
        ];
    }
}
