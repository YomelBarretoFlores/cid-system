<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\HorarioFactory;
use App\Modules\Academico\Policies\HorarioPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('aca_horarios')]
#[Fillable(['grupo_id', 'dia_semana', 'hora_inicio', 'hora_fin'])]
#[UseFactory(HorarioFactory::class)]
#[UsePolicy(HorarioPolicy::class)]
final class Horario extends Model
{
    /** @use HasFactory<HorarioFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Grupo, $this>
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
        ];
    }
}
