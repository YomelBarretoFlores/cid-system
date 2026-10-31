<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

// Content of a first basic cycle in the register of a UNASAM syllabus; the bibliography cites CILN material and the CEFR, nothing copied.
final class SilaboDeDemo
{
    /**
     * Unit, knowledge, skills, attitudes, activities and resources of each week; the last week of the period is always the review.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    private const array SEMANAS = [
        ['Unidad I: Saludos y presentaciones', 'Saludos formales e informales. El alfabeto. Pronombres personales.', 'Saluda, se presenta y presenta a otras personas.', 'Respeta los turnos de participación.', 'Exposición dialogada. Diálogos en pares. Juego de roles.', 'Texto del CILN, unidad I. Material de audio.'],
        ['Unidad II: Números y datos personales', 'Números del 0 al 100. La hora. Días de la semana y meses del año.', 'Solicita y proporciona datos personales.', 'Valora la precisión en la comunicación.', 'Práctica guiada. Dictado de números. Llenado de una ficha de datos.', 'Texto del CILN, unidad II. Fichas de práctica.'],
        ['Unidad III: La familia', 'Vocabulario de la familia. Adjetivos calificativos. Posesivos.', 'Describe a los miembros de su familia.', 'Respeta la diversidad familiar.', 'Elaboración y exposición de un árbol genealógico.', 'Texto del CILN, unidad III. Material gráfico.'],
        ['Unidad IV: La rutina diaria', 'Presente simple. Verbos de rutina. Adverbios de frecuencia.', 'Describe su rutina diaria y la de otras personas.', 'Asume con responsabilidad la organización de su tiempo.', 'Entrevista en pares. Redacción de un párrafo breve.', 'Texto del CILN, unidad IV. Aula virtual del curso.'],
    ];

    private const array REPASO = ['Integración', 'Contenidos de las unidades I a IV.', 'Sostiene una conversación breve sobre temas personales.', 'Evalúa su propio aprendizaje.', 'Presentación oral del proyecto final. Retroalimentación.', 'Texto del CILN, unidades I a IV. Rúbrica del proyecto.'];

    private const array EVALUACIONES = [
        'parcial' => ['Comprende y utiliza el vocabulario y las estructuras de las unidades I y II en textos breves.', 'Prueba escrita y prueba oral'],
        'final' => ['Se comunica en situaciones cotidianas con los contenidos de las unidades I a IV.', 'Prueba escrita y exposición oral'],
        'participacion' => ['Participa en clase, cumple las tareas asignadas y trabaja en equipo.', 'Lista de cotejo'],
    ];

    /**
     * @param  list<string>  $codigos  components of the group's scheme
     * @return array<string, mixed>
     */
    public static function completo(string $idioma, int $semanas, array $codigos): array
    {
        return [
            ...self::textos($idioma),
            'capacidades' => "Comprende expresiones de uso frecuente referidas a información personal básica.\nSe presenta y presenta a otras personas, formulando y respondiendo preguntas sobre datos personales.\nDescribe a su familia y su rutina diaria con estructuras simples.\nRedacta textos breves y sencillos sobre temas cotidianos.",
            'metodologia' => 'Se aplica el enfoque comunicativo orientado a la acción. Cada sesión comprende tres momentos: presentación del contenido, práctica controlada y práctica libre, mediante trabajo individual, en pares y en grupos. Se emplean recursos audiovisuales y el aula virtual del curso como apoyo al aprendizaje autónomo.',
            'bibliografia' => 'Centro de Idiomas y Lenguas Nativas - UNASAM. (2026). '.mb_ucfirst($idioma)." Básico I: texto del estudiante. Huaraz: CILN.\nCentro de Idiomas y Lenguas Nativas - UNASAM. (2026). Guía de pronunciación. Huaraz: CILN.\nConsejo de Europa. (2002). Marco común europeo de referencia para las lenguas: aprendizaje, enseñanza, evaluación. Madrid: Ministerio de Educación, Cultura y Deporte; Anaya.",
            'semanas' => self::semanas($semanas, $semanas),
            'evaluaciones' => self::evaluaciones($semanas, $codigos),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function borrador(string $idioma, int $semanas): array
    {
        return [...self::textos($idioma), 'semanas' => self::semanas($semanas, min(2, $semanas))];
    }

    /**
     * @return array<string, string>
     */
    private static function textos(string $idioma): array
    {
        return [
            'sumilla' => "La asignatura pertenece al nivel básico del programa de {$idioma} del Centro de Idiomas y Lenguas Nativas. Es de naturaleza teórico-práctica y tiene como propósito desarrollar en el estudiante la competencia comunicativa elemental en {$idioma}. Comprende las unidades: I. Saludos y presentaciones; II. Números y datos personales; III. La familia; IV. La rutina diaria.",
            'competencias' => "Se comunica en forma oral y escrita en {$idioma} en situaciones cotidianas elementales, utilizando expresiones y estructuras básicas con corrección y pertinencia, de acuerdo con el nivel A1 del Marco común europeo de referencia para las lenguas.",
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private static function semanas(int $total, int $llenas): array
    {
        $semanas = [];

        foreach (range(1, $llenas) as $semana) {
            $fila = $semana === $total ? self::REPASO : self::SEMANAS[($semana - 1) % count(self::SEMANAS)];
            $semanas[$semana] = array_combine(['unidad', 'saber', 'saber_hacer', 'saber_ser', 'actividades', 'recursos'], $fila);
        }

        return $semanas;
    }

    /**
     * @param  list<string>  $codigos
     * @return array<string, array<string, string|int|null>>
     */
    private static function evaluaciones(int $semanas, array $codigos): array
    {
        $evaluaciones = [];

        foreach ($codigos as $codigo) {
            [$indicadores, $instrumento] = self::EVALUACIONES[$codigo] ?? ['Demuestra los aprendizajes del ciclo.', 'Rúbrica de evaluación'];
            $evaluaciones[$codigo] = ['indicadores' => $indicadores, 'instrumento' => $instrumento, 'semana' => match ($codigo) {
                'parcial' => (int) ceil($semanas / 2),
                'final' => $semanas,
                default => null,
            }];
        }

        return $evaluaciones;
    }
}
