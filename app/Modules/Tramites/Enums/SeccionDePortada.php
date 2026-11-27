<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

// The sections of the public home page below the cover, in page order; the cover itself is always shown.
enum SeccionDePortada: string
{
    case Centro = 'centro';
    case Idiomas = 'idiomas';
    case Requisitos = 'requisitos';
    case Oferta = 'oferta';
    case Clasificacion = 'clasificacion';
    case Ruta = 'ruta';
    case Costos = 'costos';
    case Fechas = 'fechas';
    case Preguntas = 'preguntas';
    case Empieza = 'empieza';

    public function nombre(): string
    {
        return match ($this) {
            self::Centro => 'El centro',
            self::Idiomas => 'Idiomas',
            self::Requisitos => 'Cómo matricularse',
            self::Oferta => 'Horarios y vacantes',
            self::Clasificacion => 'Examen de clasificación',
            self::Ruta => 'Ruta de aprendizaje',
            self::Costos => 'Costos',
            self::Fechas => 'Calendario',
            self::Preguntas => 'Preguntas frecuentes',
            self::Empieza => 'Matrícula en línea',
        };
    }

    // The label of its link in the header and footer menus; null for a section without one.
    public function enlace(): ?string
    {
        return match ($this) {
            self::Idiomas => 'Idiomas',
            self::Requisitos => 'Matrícula',
            self::Oferta => 'Horarios',
            self::Clasificacion => 'Clasificación',
            self::Costos => 'Costos',
            self::Fechas => 'Calendario',
            self::Preguntas => 'Preguntas',
            default => null,
        };
    }

    public function tieneIntroduccion(): bool
    {
        return ! in_array($this, [self::Centro, self::Oferta, self::Fechas], true);
    }

    /**
     * The wording the page printed before it was editable.
     *
     * @return array{etiqueta: string, titulo: string, introduccion: string|null, visible: bool}
     */
    public function porDefecto(): array
    {
        [$etiqueta, $titulo, $introduccion] = match ($this) {
            self::Centro => ['El centro', 'Idiomas para la universidad y la región', null],
            self::Idiomas => ['Oferta académica', 'Idiomas', 'Cada idioma se estudia por ciclos, organizados en niveles. Al aprobar un nivel se solicita el certificado correspondiente.'],
            self::Requisitos => ['Matrícula', 'Cómo matricularse', 'Todo el proceso es en línea: cree su cuenta, registre su pago y elija un grupo con vacantes.'],
            self::Oferta => ['Oferta', 'Horarios y vacantes', null],
            self::Clasificacion => ['Examen de clasificación', '¿Ya conoce el idioma?', 'Rinda el examen de clasificación y, según su resultado, empiece en un ciclo superior sin cursar desde el ciclo I.'],
            self::Ruta => ['Plan de estudios', 'Ruta de aprendizaje', 'Cada nivel agrupa varios ciclos. El estudiante avanza de un ciclo al siguiente al aprobarlo.'],
            self::Costos => ['TUPA / TUSNE', 'Costos según su vínculo con la UNASAM', 'Pague en el Banco de la Nación, Págalo.pe o la caja de la universidad y registre su recibo en el portal.'],
            self::Fechas => ['Calendario', 'Fechas del periodo', null],
            self::Preguntas => ['Orientación', 'Preguntas frecuentes', 'Lo que más consultan los postulantes y estudiantes del CILN.'],
            self::Empieza => ['Huaraz, Áncash', 'Matrícula en línea', 'Cree su cuenta, registre su pago y elija su horario. El CILN valida su recibo y su ficha de matrícula se descarga al instante.'],
        };

        return ['etiqueta' => $etiqueta, 'titulo' => $titulo, 'introduccion' => $introduccion, 'visible' => true];
    }
}
