<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Models;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Database\Factories\TarifaFactory;
use App\Modules\Pagos\Policies\TarifaPolicy;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Support\Eloquent\RenuevaContenidoPublico;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TipoVinculo $tipo_vinculo
 * @property ?NivelCurricular $nivel
 * @property ?int $programa_id
 * @property CarbonImmutable $vigente_desde
 * @property CarbonImmutable|null $vigente_hasta
 * @property-read Concepto $concepto
 */
#[Table('pag_tarifas')]
#[Fillable(['concepto_id', 'tipo_vinculo', 'monto', 'vigente_desde', 'vigente_hasta', 'nivel', 'programa_id'])]
#[UseFactory(TarifaFactory::class)]
#[UsePolicy(TarifaPolicy::class)]
final class Tarifa extends Model
{
    /** @use HasFactory<TarifaFactory> */
    use HasFactory, RenuevaContenidoPublico;

    /**
     * @return BelongsTo<Concepto, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function vigenteEn(Builder $query, CarbonInterface $fecha): void
    {
        $query->whereDate('vigente_desde', '<=', $fecha)
            ->where(fn (Builder $q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha));
    }

    protected function casts(): array
    {
        return [
            'tipo_vinculo' => TipoVinculo::class,
            'nivel' => NivelCurricular::class,
            'monto' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }
}
