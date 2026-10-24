<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Models;

use App\Modules\Matricula\Database\Factories\MatriculaFactory;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Policies\MatriculaPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EstadoMatricula $estado
 * @property CondicionMatricula $condicion
 * @property CarbonImmutable $matriculado_en
 * @property CarbonImmutable|null $retirado_en
 */
#[Table('mat_matriculas')]
#[RouteKey('ulid')]
#[Fillable(['persona_id', 'grupo_id', 'pago_id', 'condicion', 'estado', 'matriculado_en', 'retirado_en'])]
#[UseFactory(MatriculaFactory::class)]
#[UsePolicy(MatriculaPolicy::class)]
final class Matricula extends Model
{
    /** @use HasFactory<MatriculaFactory> */
    use HasFactory, HasUlids;

    protected $attributes = [
        'estado' => 'activa',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * @return HasMany<Movimiento, $this>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('efectuado_en');
    }

    protected function casts(): array
    {
        return [
            'condicion' => CondicionMatricula::class,
            'estado' => EstadoMatricula::class,
            'matriculado_en' => 'datetime',
            'retirado_en' => 'datetime',
        ];
    }
}
