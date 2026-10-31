<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

// Generic content of a first basic cycle; the bibliography names CILN material, not real titles.
final class SilaboDeDemo
{
    /**
     * Unit, knowledge, skills, attitudes, activities and resources of each week; the last week of the period is always the review.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    private const array SEMANAS = [
        ['Unidad 1: Saludos y presentaciones', 'Saludos formales e informales, el alfabeto y los pronombres personales.', 'Se presenta y presenta a otras personas.', 'Respeta los turnos de palabra.', 'Diálogos en parejas y juego de roles de presentación.', 'Material del CILN, Unidad 1; audios de la unidad.'],
        ['Unidad 2: Números y datos personales', 'Números del 0 al 100, la hora, los días y los meses.', 'Pide y da datos personales: edad, teléfono y dirección.', 'Valora la precisión al comunicar datos.', 'Llenado de una ficha de inscripción y dictado de números.', 'Material del CILN, Unidad 2; fichas de trabajo.'],
        ['Unidad 3: La familia y las personas', 'Vocabulario de la familia, adjetivos y posesivos.', 'Describe a su familia y a sus compañeros.', 'Muestra respeto por la diversidad de las familias.', 'Árbol genealógico comentado y descripción oral.', 'Material del CILN, Unidad 3; imágenes de apoyo.'],
        ['Unidad 4: La rutina diaria', 'Presente simple, verbos de rutina y adverbios de frecuencia.', 'Cuenta su día y pregunta por la rutina de otros.', 'Organiza su tiempo de estudio con responsabilidad.', 'Entrevista sobre rutinas y redacción de un párrafo breve.', 'Material del CILN, Unidad 4; plataforma del curso.'],
    ];

    private const array REPASO = ['Repaso integrador', 'Contenidos de las unidades 1 a 4.', 'Mantiene una conversación breve sobre sí mismo.', 'Evalúa su propio avance.', 'Proyecto final oral en grupos y retroalimentación.', 'Material del CILN, unidades 1 a 4; rúbrica del proyecto.'];

    private const array EVALUACIONES = [
        'parcial' => ['Comprende y usa el vocabulario de las unidades 1 y 2 en textos breves.', 'Prueba escrita y oral'],
        'final' => ['Se comunica en situaciones cotidianas con el contenido de todo el ciclo.', 'Prueba escrita y exposición oral'],
        'participacion' => ['Participa en clase, cumple las tareas y colabora con sus compañeros.', 'Lista de cotejo'],
    ];

    /**
     * @param  list<string>  $codigos  components of the group's scheme
     * @return array<string, mixed>
     */
    public static function completo(string $idioma, int $semanas, array $codigos): array
    {
        return [
            ...self::textos($idioma),
            'capacidades' => "Comprende y produce textos orales y escritos breves en {$idioma} sobre su entorno inmediato; usa el vocabulario y las estructuras básicas del ciclo.",
            'metodologia' => 'Enfoque comunicativo: cada sesión combina presentación, práctica guiada y práctica libre en parejas o grupos, con uso de audios y material del curso.',
            'bibliografia' => "Material del CILN, Básico I de {$idioma}, unidades 1 a 4.\nGuía de pronunciación del CILN.\nDiccionario bilingüe de uso escolar.",
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
            'sumilla' => "Curso teórico-práctico que inicia al estudiante en el {$idioma} de uso cotidiano: saludos, datos personales, la familia y la rutina diaria.",
            'competencias' => "Se comunica en {$idioma} en situaciones cotidianas sencillas, de forma oral y escrita, con un vocabulario básico.",
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
            [$indicadores, $instrumento] = self::EVALUACIONES[$codigo] ?? ['Demuestra los aprendizajes del ciclo.', 'Rúbrica'];
            $evaluaciones[$codigo] = ['indicadores' => $indicadores, 'instrumento' => $instrumento, 'semana' => match ($codigo) {
                'parcial' => (int) ceil($semanas / 2),
                'final' => $semanas,
                default => null,
            }];
        }

        return $evaluaciones;
    }
}
