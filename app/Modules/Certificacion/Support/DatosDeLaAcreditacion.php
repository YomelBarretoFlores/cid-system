<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Data\TextosDeLosDocumentos;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Tramites\Contracts\TitulosDeGrados;
use App\Shared\Support\Notas\Nota;

// The rows of the real accreditation format, frozen at issue: grade, modality, completion date and validity sentence; modality and validity come from the editable texts.
final class DatosDeLaAcreditacion
{
    /**
     * @param  list<NotaDelHistorial>  $notas  passed cycles of the level
     * @return array{calificativo: ?int, modalidad: string, fecha_nivel: ?string, validez: string}
     */
    public static function de(array $notas, string $mencion, TextosDeLosDocumentos $textos): array
    {
        return [
            'calificativo' => self::calificativo($notas),
            'modalidad' => self::porExamen($notas) ? $textos->suficienciaAcreditacion : $textos->programaAcreditacion,
            'fecha_nivel' => self::fecha($notas),
            'validez' => str_replace('{mencion}', self::grado($mencion), $textos->validezAcreditacion),
        ];
    }

    /**
     * The level's final grade rounded half up to an integer, as the actas round their grades.
     *
     * @param  list<NotaDelHistorial>  $notas
     */
    private static function calificativo(array $notas): ?int
    {
        $final = HistorialDeNotas::notaFinal($notas);

        return $final === null ? null : intdiv(Nota::centesimos($final) + 50, 100);
    }

    /**
     * Proficiency when the level was passed by the exam and no cycle was taken in class; placement cycles count for neither.
     *
     * @param  list<NotaDelHistorial>  $notas
     */
    private static function porExamen(array $notas): bool
    {
        $formas = array_map(fn (NotaDelHistorial $nota): FormaDeAprobacion => $nota->forma, $notas);

        return in_array(FormaDeAprobacion::Suficiencia, $formas, true) && ! in_array(FormaDeAprobacion::Matricula, $formas, true);
    }

    /**
     * @param  list<NotaDelHistorial>  $notas
     */
    private static function fecha(array $notas): ?string
    {
        $fechas = array_filter(array_map(fn (NotaDelHistorial $nota): ?string => $nota->fecha, $notas));

        return $fechas === [] ? null : max($fechas);
    }

    // "Maestría en Educación" is the degree of "Maestro en Educación" by the FUT grades catalog; any other mention is printed as written.
    private static function grado(string $mencion): string
    {
        foreach (app(TitulosDeGrados::class)() as $nombre => $titulo) {
            if (preg_match('/^'.preg_quote($nombre, '/').'(?![\pL\pN])/iu', $mencion, $marca) === 1) {
                $mencion = $titulo.mb_substr($mencion, mb_strlen($marca[0]));

                break;
            }
        }

        return $mencion;
    }
}
