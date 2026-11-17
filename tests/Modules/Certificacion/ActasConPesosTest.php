<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Certificacion\Models\Acta;

it('reads the components of an acta closed with weights, taking the exams from their codes', function (): void {
    $acta = new Acta(['componentes' => [
        ['id' => 1, 'codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00'],
        ['id' => 2, 'codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40.00'],
        ['id' => 3, 'codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20.00'],
        ['id' => 4, 'codigo' => 'oral', 'nombre' => 'Expresión oral', 'grupo' => 'habilidades'],
    ]]);

    expect($acta->componentesDeEvaluacion())->toEqual([
        new ComponenteDeEvaluacion(1, 'parcial', 'Examen parcial', GrupoDeComponente::Examenes),
        new ComponenteDeEvaluacion(2, 'final', 'Examen final', GrupoDeComponente::Examenes),
        new ComponenteDeEvaluacion(3, 'participacion', 'Participación', GrupoDeComponente::Habilidades),
        new ComponenteDeEvaluacion(4, 'oral', 'Expresión oral', GrupoDeComponente::Habilidades),
    ]);
});
