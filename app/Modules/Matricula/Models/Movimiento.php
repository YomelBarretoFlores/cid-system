<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use App\Modules\Matricula\Database\Factories\MovimientoFactory;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('mat_movimientos')]
#[Fillable(['matricula_id', 'tipo', 'grupo_origen_id', 'grupo_destino_id', 'motivo', 'autorizado_por', 'efectuado_en'])]
#[UseFactory(MovimientoFactory::class)]
final class Movimiento extends Model
{
    /** @use HasFactory<MovimientoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Matricula, $this>
     */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'efectuado_en' => 'datetime',
        ];
    }
}
