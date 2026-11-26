<?php

declare(strict_types=1);

namespace App\Modules\Personas\Support;

use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoDocumento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

// The one format rule for identity documents, read from the catalog; forms show it per field and ComprobarDocumento enforces it.
final readonly class DocumentoValido implements ValidationRule
{
    public const string TIPO_NO_DISPONIBLE = 'Elige un tipo de documento disponible.';

    private function __construct(private ?TipoDocumento $tipo, private ?Persona $guardada, private bool $formal) {}

    /**
     * An unknown type passes here because the type field reports it. For a person being edited, their stored type is
     * accepted even if inactive and their stored document is not checked again, as ComprobarDocumento does.
     */
    public static function para(mixed $codigo, ?Persona $guardada = null, bool $formal = true): self
    {
        return new self(self::tipo($codigo, $guardada?->tipo_documento), $guardada, $formal);
    }

    public static function tipo(mixed $codigo, ?string $guardado = null): ?TipoDocumento
    {
        if (! is_string($codigo) || $codigo === '') {
            return null;
        }

        return TipoDocumento::query()
            ->where('codigo', $codigo)
            ->where(fn (Builder $query): Builder => $query->where('activo', true)->orWhere('codigo', $guardado))
            ->first();
    }

    public static function error(TipoDocumento $tipo, string $numero, bool $formal = true): ?string
    {
        $caracteres = $tipo->solo_digitos ? '[0-9]' : '[A-Z0-9]';
        $patron = "/^{$caracteres}{{$tipo->longitud_minima},{$tipo->longitud_maxima}}$/";

        return preg_match($patron, Persona::normalizarDocumento($numero)) === 1 ? null : self::mensaje($tipo, $formal);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $numero = is_string($value) ? $value : '';

        if (! $this->tipo instanceof TipoDocumento || $this->sinCambios($numero)) {
            return;
        }

        $error = self::error($this->tipo, $numero, $this->formal);

        if ($error !== null) {
            $fail($error);
        }
    }

    private function sinCambios(string $numero): bool
    {
        return $this->guardada instanceof Persona
            && $this->guardada->tipo_documento === $this->tipo?->codigo
            && $this->guardada->numero_documento === Persona::normalizarDocumento($numero);
    }

    private static function mensaje(TipoDocumento $tipo, bool $formal): string
    {
        $exacta = $tipo->longitud_minima === $tipo->longitud_maxima;
        $sujeto = $exacta ? "El {$tipo->nombre}" : 'El documento';
        $cantidad = $exacta ? (string) $tipo->longitud_minima : "de {$tipo->longitud_minima} a {$tipo->longitud_maxima}";
        $caracteres = $tipo->solo_digitos ? 'dígitos' : 'letras o números';

        if (! $formal) {
            return "{$sujeto} tiene {$cantidad} {$caracteres}.";
        }

        return sprintf('%s debe tener %s%s %s%s.', $sujeto, $exacta ? 'exactamente ' : '', $cantidad, $caracteres, $tipo->solo_digitos ? '' : ', sin espacios ni guiones');
    }
}
