<?php

declare(strict_types=1);

namespace App\Shared\Ui;

// Filament needs the palettes in PHP; they mirror resources/css/ciln/tokens.css, and PaletaCilnTest keeps both equal.
final class PaletaCiln
{
    /** @var array<int, string> */
    public const array PIEDRA = [
        50 => '#fbfaf7',
        100 => '#f4f3ef',
        200 => '#eae9e5',
        300 => '#d9d4c8',
        400 => '#a39e92',
        500 => '#8d887c',
        600 => '#6e695e',
        700 => '#5e5a50',
        800 => '#36322a',
        900 => '#1d1b16',
        950 => '#12110d',
    ];

    /** @var array<int, string> */
    public const array ORO = [
        50 => 'oklch(0.975 0.018 85)',
        100 => 'oklch(0.95 0.04 82)',
        200 => 'oklch(0.9 0.08 85)',
        300 => 'oklch(0.86 0.13 80)',
        400 => 'oklch(0.8 0.14 75)',
        500 => 'oklch(0.72 0.14 70)',
        600 => 'oklch(0.6 0.13 67)',
        700 => 'oklch(0.5 0.11 65)',
        800 => 'oklch(0.42 0.1 60)',
        900 => 'oklch(0.34 0.08 60)',
        950 => 'oklch(0.29 0.06 80)',
    ];
}
