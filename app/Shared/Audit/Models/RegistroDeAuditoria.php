<?php

declare(strict_types=1);

namespace App\Shared\Audit\Models;

use App\Shared\Audit\Enums\OperacionAuditada;
use App\Shared\Audit\Policies\RegistroDeAuditoriaPolicy;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rows are written only by the aud_registrar() trigger; the database refuses UPDATE and DELETE.
 *
 * @property OperacionAuditada $operacion
 * @property array<string, mixed>|null $datos_anteriores
 * @property array<string, mixed>|null $datos_nuevos
 */
#[Table('aud_registros', timestamps: false)]
#[UsePolicy(RegistroDeAuditoriaPolicy::class)]
final class RegistroDeAuditoria extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected static function booted(): void
    {
        self::saving(fn (): bool => false);
        self::deleting(fn (): bool => false);
    }

    protected function casts(): array
    {
        return [
            'registro_id' => 'integer',
            'operacion' => OperacionAuditada::class,
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'ocurrido_en' => 'immutable_datetime',
        ];
    }
}
