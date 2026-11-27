<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Data\TextosDeLaPortada;
use App\Modules\Tramites\Enums\SeccionDePortada;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// The cover, section and footer texts of the home page, trimmed and checked; a group left out of the data keeps its current value.
final readonly class LimpiezaDeTextosDePortada
{
    public const int MAXIMO_DE_REDES = 6;

    public function __construct(private TextosDeLaPortada $actuales) {}

    /**
     * @param  array<string, mixed>  $datos
     * @return array{subtitulo: string, boton_principal: string, boton_secundario: string}
     */
    public function portada(array $datos): array
    {
        return [
            'subtitulo' => $this->requerido($datos['subtitulo'] ?? $this->actuales->subtitulo, 200, 'el texto bajo el nombre del centro'),
            'boton_principal' => $this->requerido($datos['boton_principal'] ?? $this->actuales->botonPrincipal, 40, 'el botón principal'),
            'boton_secundario' => $this->requerido($datos['boton_secundario'] ?? $this->actuales->botonSecundario, 40, 'el botón secundario'),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{secciones: array<string, array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}>}
     */
    public function secciones(array $datos): array
    {
        $recibidas = is_array($datos['secciones'] ?? null) ? $datos['secciones'] : [];
        $desconocidas = array_diff(array_map(strval(...), array_keys($recibidas)), array_column(SeccionDePortada::cases(), 'value'));
        throw_if($desconocidas !== [], ReglaDeNegocioIncumplida::class, 'La portada no tiene la sección '.implode(', ', $desconocidas).'.');

        $secciones = [];

        foreach (SeccionDePortada::cases() as $seccion) {
            $recibida = $recibidas[$seccion->value] ?? null;
            $secciones[$seccion->value] = is_array($recibida) ? $this->seccion($seccion, $recibida) : $this->actuales->secciones[$seccion->value];
        }

        return ['secciones' => $secciones];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{pie: string, correo: string|null, horario_atencion: string|null, redes: list<array{etiqueta: string, url: string}>}
     */
    public function pie(array $datos): array
    {
        $correo = $this->opcional(array_key_exists('correo', $datos) ? $datos['correo'] : $this->actuales->correo, 120, 'el correo de contacto');
        throw_if($correo !== null && filter_var($correo, FILTER_VALIDATE_EMAIL) === false, ReglaDeNegocioIncumplida::class, 'Escribe un correo de contacto válido.');

        return [
            'pie' => $this->requerido($datos['pie'] ?? $this->actuales->pie, 300, 'la descripción del pie de página'),
            'correo' => $correo,
            'horario_atencion' => $this->opcional(array_key_exists('horario_atencion', $datos) ? $datos['horario_atencion'] : $this->actuales->horarioAtencion, 150, 'el horario de atención'),
            'redes' => array_key_exists('redes', $datos) ? $this->redes($datos['redes']) : $this->actuales->redes,
        ];
    }

    /**
     * @param  array<mixed>  $recibida
     * @return array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}
     */
    private function seccion(SeccionDePortada $seccion, array $recibida): array
    {
        $nombre = "la sección «{$seccion->nombre()}»";

        return [
            'etiqueta' => $this->requerido($recibida['etiqueta'] ?? null, 60, "la etiqueta de {$nombre}"),
            'titulo' => $this->requerido($recibida['titulo'] ?? null, 120, "el título de {$nombre}"),
            'introduccion' => $seccion->tieneIntroduccion() ? $this->opcional($recibida['introduccion'] ?? null, 400, "la introducción de {$nombre}") : null,
            'visible' => filter_var($recibida['visible'] ?? true, FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * @return list<array{etiqueta: string, url: string}>
     */
    private function redes(mixed $redes): array
    {
        $filas = is_array($redes) ? array_values($redes) : [];
        throw_if(count($filas) > self::MAXIMO_DE_REDES, ReglaDeNegocioIncumplida::class, 'Registra hasta '.self::MAXIMO_DE_REDES.' enlaces en el pie de página.');

        return array_map(fn (mixed $fila): array => [
            'etiqueta' => $this->requerido(is_array($fila) ? ($fila['etiqueta'] ?? null) : null, 40, 'el nombre de cada enlace'),
            'url' => $this->https(is_array($fila) ? ($fila['url'] ?? null) : null),
        ], $filas);
    }

    private function https(mixed $valor): string
    {
        $url = $this->requerido($valor, 255, 'la dirección de cada enlace');
        $valida = filter_var($url, FILTER_VALIDATE_URL) !== false && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
        throw_unless($valida, ReglaDeNegocioIncumplida::class, 'Cada enlace del pie de página debe ser una dirección https://.');

        return $url;
    }

    private function requerido(mixed $valor, int $maximo, string $campo): string
    {
        $texto = $this->opcional($valor, $maximo, $campo);
        throw_if($texto === null, ReglaDeNegocioIncumplida::class, "Completa {$campo}, hasta {$maximo} caracteres.");

        return $texto;
    }

    private function opcional(mixed $valor, int $maximo, string $campo): ?string
    {
        $texto = trim(is_string($valor) ? $valor : '');
        throw_if(mb_strlen($texto) > $maximo, ReglaDeNegocioIncumplida::class, "Completa {$campo}, hasta {$maximo} caracteres.");

        return $texto === '' ? null : $texto;
    }
}
