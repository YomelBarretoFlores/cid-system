<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Models;

use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Policies\FirmantePolicy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $cargo
 * @property string $nombre
 * @property bool $firma_certificados
 * @property bool $firma_constancias
 * @property bool $firma_actas
 * @property int $orden
 * @property CarbonInterface $vigente_desde
 * @property ?CarbonInterface $vigente_hasta
 */
#[Table('cer_firmantes')]
#[Fillable(['cargo', 'nombre', 'firma_certificados', 'firma_constancias', 'firma_actas', 'orden', 'vigente_desde', 'vigente_hasta'])]
#[UsePolicy(FirmantePolicy::class)]
final class Firmante extends Model
{
    /**
     * Signers of a kind of document in force on a date, in their printed order.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function firmanEn(Builder $query, GrupoDeFirma $grupo, CarbonInterface $fecha): void
    {
        $query->where($grupo->value, true)
            ->where('vigente_desde', '<=', $fecha->toDateString())
            ->where(fn (Builder $vigencia): Builder => $vigencia->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha->toDateString()))
            ->orderBy('orden')
            ->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'firma_certificados' => 'boolean',
            'firma_constancias' => 'boolean',
            'firma_actas' => 'boolean',
            'orden' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }
}
