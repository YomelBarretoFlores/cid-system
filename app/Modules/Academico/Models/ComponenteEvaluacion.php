<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Database\Factories\ComponenteEvaluacionFactory;
use App\Modules\Academico\Enums\GrupoDeComponente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $esquema_id
 * @property string $codigo
 * @property string $nombre
 * @property GrupoDeComponente $grupo
 * @property int $orden
 */
#[Table('aca_componentes_evaluacion')]
#[Fillable(['esquema_id', 'codigo', 'nombre', 'grupo', 'orden'])]
#[UseFactory(ComponenteEvaluacionFactory::class)]
final class ComponenteEvaluacion extends Model
{
    /** @use HasFactory<ComponenteEvaluacionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EsquemaEvaluacion, $this>
     */
    public function esquema(): BelongsTo
    {
        return $this->belongsTo(EsquemaEvaluacion::class, 'esquema_id');
    }

    protected function casts(): array
    {
        return [
            'grupo' => GrupoDeComponente::class,
            'orden' => 'integer',
        ];
    }
}
