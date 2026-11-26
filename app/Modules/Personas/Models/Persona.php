<?php

declare(strict_types=1);

namespace App\Modules\Personas\Models;

use App\Modules\Personas\Database\Factories\PersonaFactory;
use App\Modules\Personas\Policies\PersonaPolicy;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $tipo_vinculo
 * @property string $tipo_documento
 * @property ?CarbonImmutable $fecha_nacimiento
 * @property ?string $direccion
 * @property ?string $foto
 * @property ?CarbonImmutable $tratamiento_aceptado_en
 * @property ?CarbonImmutable $habilitada_en
 * @property-read TipoVinculo $vinculo
 * @property-read TipoDocumento $tipoDeDocumento
 */
#[Table('per_personas')]
#[RouteKey('ulid')]
#[Fillable([
    'user_id', 'tipo_documento', 'numero_documento', 'apellido_paterno', 'apellido_materno',
    'nombres', 'fecha_nacimiento', 'correo', 'celular', 'direccion', 'foto', 'tipo_vinculo',
])]
#[UseFactory(PersonaFactory::class)]
#[UsePolicy(PersonaPolicy::class)]
final class Persona extends Model
{
    private const string CON_TILDE = 'ÁÀÄÂÉÈËÊÍÌÏÎÓÒÖÔÚÙÜÛÑáàäâéèëêíìïîóòöôúùüûñ';

    private const string SIN_TILDE = 'AAAAEEEEIIIIOOOOUUUUNaaaaeeeeiiiioooouuuun';

    /** @use HasFactory<PersonaFactory> */
    use HasFactory, HasUlids;

    public const string FORMATO_CELULAR = '/^\+?[0-9]{7,15}$/';

    #[\Override]
    protected $attributes = [
        'tipo_vinculo' => 'publico_general',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public static function normalizarDocumento(string $numero): string
    {
        return mb_strtoupper(trim($numero));
    }

    public static function normalizarCelular(string $celular): string
    {
        return (string) preg_replace('/[\s().-]/', '', $celular);
    }

    // Withdrawing consent is an ARCO request, so a recorded consent is never cleared here.
    public function registrarConsentimiento(bool $aceptado): void
    {
        if ($aceptado && $this->tratamiento_aceptado_en === null) {
            $this->tratamiento_aceptado_en = now();
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TipoVinculo, $this>
     */
    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(TipoVinculo::class, 'tipo_vinculo', 'codigo');
    }

    /**
     * @return BelongsTo<TipoDocumento, $this>
     */
    public function tipoDeDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento', 'codigo');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function coincideCon(Builder $query, string $termino): void
    {
        $termino = addcslashes(trim($termino), '\\%_');

        // translate() + ILIKE instead of a nondeterministic collation: PostgreSQL before 18 rejects LIKE on those.
        $query->where(fn (Builder $coincidencia) => $coincidencia
            ->whereLike('numero_documento', mb_strtoupper($termino).'%', caseSensitive: true)
            ->orWhereRaw(
                "translate(concat_ws(' ', apellido_paterno, apellido_materno, nombres), ?, ?) ILIKE ?",
                [self::CON_TILDE, self::SIN_TILDE, '%'.Str::ascii($termino).'%'],
            ));
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->apellido_paterno} {$this->apellido_materno}").", {$this->nombres}");
    }

    /**
     * @return Attribute<non-falsy-string, never>
     */
    protected function etiqueta(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->tipoDeDocumento->nombre} {$this->numero_documento} · {$this->nombre_completo}");
    }

    /**
     * @return Attribute<string, string>
     */
    protected function numeroDocumento(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizarDocumento($value));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function celular(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : self::normalizarCelular($value));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function correo(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : mb_strtolower(trim($value)));
    }

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'tratamiento_aceptado_en' => 'datetime',
            'habilitada_en' => 'datetime',
        ];
    }
}
