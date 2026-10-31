<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Contracts\SilaboEnPdf;
use App\Modules\Docencia\Models\CargaAcademica;
use Tests\Support\ActasFinales;
use Tests\Support\Silabos;

beforeEach(function (): void {
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
});

it('has nothing to read for a group without syllabus or an unknown group', function (): void {
    expect(app(LecturaDelSilabo::class)($this->grupo->id))->toBeNull()
        ->and(app(LecturaDelSilabo::class)(999_999))->toBeNull();
});

it('fills section I from the group, with placeholders for what coordination has not assigned yet', function (): void {
    Silabos::borrador($this->grupo, $this->curso['docente']);
    CargaAcademica::query()->where('grupo_id', $this->grupo->id)->delete();

    $silabo = app(LecturaDelSilabo::class)($this->grupo->id);

    expect($silabo->estaPublicado())->toBeFalse()
        ->and([$silabo->grupo, $silabo->curso])->toBe(['ING-1-M', 'Inglés · Ciclo 1'])
        ->and($silabo->identificacion)->toBe([
            'Grupo' => 'ING-1-M', 'Idioma' => 'Inglés', 'Ciclo' => '1 · Básico I', 'Nivel' => 'Básico', 'Periodo' => 'Marzo 2027 (1 mar 2027 al 31 mar 2027)',
            'Horas lectivas' => '60', 'Horario' => 'Por confirmar', 'Aula' => 'Por asignar', 'Docente' => 'Por asignar',
        ]);
});

it('names a virtual class without printing its link', function (): void {
    Grupo::query()->whereKey($this->grupo->id)->update(['aula_id' => null, 'enlace_virtual' => 'https://meet.example.test/ing-1-m']);
    Silabos::borrador($this->grupo, $this->curso['docente']);

    expect(app(LecturaDelSilabo::class)($this->grupo->id)->identificacion['Aula'])->toBe('Clase virtual');
});

it('marks the PDF of a draft so it is not taken for the published syllabus', function (): void {
    Silabos::borrador($this->grupo, $this->curso['docente']);

    $pdf = app(SilaboEnPdf::class)(app(LecturaDelSilabo::class)($this->grupo->id));

    expect($pdf->contains('BORRADOR · aún no publicado para los estudiantes'))->toBeTrue()
        ->and($pdf->contains('Publicado el'))->toBeFalse();
});

it('prints the publication date of a published syllabus in Lima', function (): void {
    $this->travelTo(now()->setTimezone('America/Lima')->setDate(2027, 2, 25)->setTime(16, 30));
    Silabos::publicar($this->grupo, $this->curso['docente'], $this->curso['usuario']);

    $pdf = app(SilaboEnPdf::class)(app(LecturaDelSilabo::class)($this->grupo->id));

    expect($pdf->contains('25/02/2027 16:30'))->toBeTrue()
        ->and($pdf->contains('BORRADOR'))->toBeFalse();
});
