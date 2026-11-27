<?php

declare(strict_types=1);

namespace App\Modules\Academico\Models;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Database\Factories\ComponenteEvaluacionFactory;
use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;
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
 * @property TipoDeComponente $tipo
 * @property ?CalculoDeComponente $calculo
 * @property RedondeoDeNota $redondeo
 * @property list<array{codigo: string, peso: int}> $fuentes
 * @property ?int $ciclo
 * @property int $orden
 */
#[Table('aca_componentes_evaluacion')]
#[Fillable(['esquema_id', 'codigo', 'nombre', 'tipo', 'calculo', 'redondeo', 'fuentes', 'ciclo', 'orden'])]
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

    public function aDatos(): ComponenteDeEvaluacion
    {
        return new ComponenteDeEvaluacion($this->id, $this->codigo, $this->nombre, $this->tipo, $this->calculo, $this->redondeo, $this->fuentes, $this->ciclo);
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoDeComponente::class,
            'calculo' => CalculoDeComponente::class,
            'redondeo' => RedondeoDeNota::class,
            'fuentes' => 'array',
            'ciclo' => 'integer',
            'orden' => 'integer',
        ];
    }
}
