<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Models\Institucion;

final readonly class DatosDeLaInstitucion
{
    public function __construct(
        public string $universidad = 'Universidad Nacional Santiago Antúnez de Mayolo',
        public string $lema = 'Una Nueva Universidad para el Desarrollo',
        public string $centro = 'Centro de Idiomas y Lenguas Nativas',
        public string $direccion = 'Prolongación Simón Bolívar S/N – Huaraz',
        public string $telefono = '043-640020 / Anexo 3105',
        public string $web = 'cid.unasam.edu.pe',
        public string $facebook = 'cidunasam',
        public string $ciudad = 'Huaraz',
        public string $formatoNumero = 'N.º {numero:06}-{anio}',
        public string $formatoNumeroConstanciaNotas = 'N° {anio} - {numero:05}',
    ) {}

    public static function desde(Institucion $institucion): self
    {
        return new self(
            universidad: $institucion->universidad,
            lema: $institucion->lema,
            centro: $institucion->centro,
            direccion: $institucion->direccion,
            telefono: $institucion->telefono,
            web: $institucion->web,
            facebook: $institucion->facebook,
            ciudad: $institucion->ciudad,
            formatoNumero: $institucion->formato_numero,
            formatoNumeroConstanciaNotas: $institucion->formato_numero_constancia_notas,
        );
    }

    // "{anio}" is the year and "{numero:5}" (or "{numero:05}") the number padded with zeros to that width; "{numero}" is not padded.
    public static function numerar(string $formato, int $numero, int $anio): string
    {
        $conNumero = (string) preg_replace_callback(
            '/\{numero(?::(\d+))?\}/',
            fn (array $marca): string => str_pad((string) $numero, min((int) ($marca[1] ?? 0), 20), '0', STR_PAD_LEFT),
            $formato,
        );

        return str_replace('{anio}', (string) $anio, $conNumero);
    }
}
