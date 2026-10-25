<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Models;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property EstadoTramite $estado
 * @property CarbonImmutable $created_at
 */
#[Table('tra_movimientos')]
#[Fillable(['tramite_id', 'estado', 'etapa', 'etapa_nombre', 'comentario', 'actor_id'])]
final class Movimiento extends Model
{
    public const null UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'estado' => EstadoTramite::class,
            'etapa' => 'integer',
        ];
    }
}
