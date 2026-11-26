<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Enums;

// Each case is a column of sis_parametros; the ranges match the table CHECKs.
enum Parametro: string
{
    case DiasVigenciaClasificacion = 'dias_vigencia_clasificacion';
    case PuntajeMaximoClasificacion = 'puntaje_maximo_clasificacion';
    case InicioAbreMinutosAntes = 'asistencia_inicio_abre_minutos_antes';
    case CierreAbreMinutosAntesDelFin = 'asistencia_cierre_abre_minutos_antes_del_fin';
    case CierreCierraMinutosDespuesDelFin = 'asistencia_cierre_cierra_minutos_despues_del_fin';
    case ConfirmacionMinutosDespuesDelFin = 'asistencia_confirmacion_minutos_despues_del_fin';
    case MaximoAdjuntosTramite = 'maximo_adjuntos_tramite';
    case AdjuntoMaximoMb = 'adjunto_maximo_mb';
    case ReciboMaximoMb = 'recibo_maximo_mb';
    case FotoMaximoMb = 'foto_maximo_mb';
    case FotoLadoMinimoPx = 'foto_lado_minimo_px';
    case FotoLadoMaximoPx = 'foto_lado_maximo_px';
    case UmbralPocasVacantes = 'umbral_pocas_vacantes';

    /**
     * @return array{0: string, 1: int, 2: int, 3: int}
     */
    private function definicion(): array
    {
        return match ($this) {
            self::DiasVigenciaClasificacion => ['La vigencia de la solicitud de examen de clasificación (días)', 1, 365, 30],
            self::PuntajeMaximoClasificacion => ['El puntaje máximo del examen de clasificación', 10, 100, 100],
            self::InicioAbreMinutosAntes => ['La apertura del llamado de inicio (minutos antes del inicio)', 0, 60, 10],
            self::CierreAbreMinutosAntesDelFin => ['La apertura del llamado de cierre (minutos antes del fin)', 0, 60, 15],
            self::CierreCierraMinutosDespuesDelFin => ['El cierre del llamado de cierre (minutos después del fin)', 0, 240, 30],
            self::ConfirmacionMinutosDespuesDelFin => ['El plazo de confirmación del delegado (minutos después del fin)', 30, 1440, 120],
            self::MaximoAdjuntosTramite => ['El máximo de adjuntos por trámite', 1, 10, 5],
            self::AdjuntoMaximoMb => ['El tamaño máximo de cada adjunto de trámite (MB)', 1, 10, 5],
            self::ReciboMaximoMb => ['El tamaño máximo del recibo de pago (MB)', 1, 10, 5],
            self::FotoMaximoMb => ['El tamaño máximo de la fotografía (MB)', 1, 10, 2],
            self::FotoLadoMinimoPx => ['El lado mínimo de la fotografía (píxeles)', 100, 1000, 200],
            self::FotoLadoMaximoPx => ['El lado máximo de la fotografía (píxeles)', 1000, 8000, 3000],
            self::UmbralPocasVacantes => ['El aviso de pocas vacantes (vacantes o menos)', 0, 50, 5],
        };
    }

    public function etiqueta(): string
    {
        return $this->definicion()[0];
    }

    public function minimo(): int
    {
        return $this->definicion()[1];
    }

    public function maximo(): int
    {
        return $this->definicion()[2];
    }

    public function porDefecto(): int
    {
        return $this->definicion()[3];
    }
}
