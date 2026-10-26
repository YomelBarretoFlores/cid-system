<?php

declare(strict_types=1);

namespace App\Shared\Ui;

use Illuminate\Support\Facades\Blade;

enum BanderaDeIdioma: string
{
    case EstadosUnidos = 'estados_unidos';
    case ReinoUnido = 'reino_unido';
    case Francia = 'francia';
    case Italia = 'italia';
    case Alemania = 'alemania';
    case Brasil = 'brasil';
    case Portugal = 'portugal';
    case Espana = 'espana';
    case Rusia = 'rusia';
    case China = 'china';
    case Japon = 'japon';
    case CoreaDelSur = 'corea_del_sur';
    case Wiphala = 'wiphala';
    case Neutra = 'neutra';

    public function etiqueta(): string
    {
        return match ($this) {
            self::EstadosUnidos => 'Estados Unidos',
            self::ReinoUnido => 'Reino Unido',
            self::Francia => 'Francia',
            self::Italia => 'Italia',
            self::Alemania => 'Alemania',
            self::Brasil => 'Brasil',
            self::Portugal => 'Portugal',
            self::Espana => 'España',
            self::Rusia => 'Rusia',
            self::China => 'China',
            self::Japon => 'Japón',
            self::CoreaDelSur => 'Corea del Sur',
            self::Wiphala => 'Wiphala (lenguas originarias)',
            self::Neutra => 'Sin bandera (hoja dorada)',
        };
    }

    // For languages without a stored flag: the six names drawn before keep their flag, and other known names now get theirs.
    public static function sugeridaPara(string $idioma): self
    {
        return match (mb_strtolower(trim($idioma))) {
            'inglés', 'ingles' => self::EstadosUnidos,
            'francés', 'frances' => self::Francia,
            'italiano' => self::Italia,
            'alemán', 'aleman' => self::Alemania,
            'portugués', 'portugues' => self::Brasil,
            'español', 'espanol' => self::Espana,
            'ruso' => self::Rusia,
            'chino', 'chino mandarín', 'mandarín' => self::China,
            'japonés', 'japones' => self::Japon,
            'coreano' => self::CoreaDelSur,
            'quechua', 'aimara', 'aymara' => self::Wiphala,
            default => self::Neutra,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $bandera): array => [
            $bandera->value => Blade::render('<span class="flex items-center gap-2.5"><x-ciln.hoja-bandera :bandera="$bandera" size="22" />{{ $bandera->etiqueta() }}</span>', ['bandera' => $bandera]),
        ])->all();
    }
}
