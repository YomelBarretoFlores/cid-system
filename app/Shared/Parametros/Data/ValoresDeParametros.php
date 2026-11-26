<?php

declare(strict_types=1);

namespace App\Shared\Parametros\Data;

use App\Shared\Parametros\Enums\Parametro;

final readonly class ValoresDeParametros
{
    public function __construct(
        public int $diasVigenciaClasificacion = 30,
        public int $puntajeMaximoClasificacion = 100,
        public int $inicioAbreMinutosAntes = 10,
        public int $cierreAbreMinutosAntesDelFin = 15,
        public int $cierreCierraMinutosDespuesDelFin = 30,
        public int $confirmacionMinutosDespuesDelFin = 120,
        public int $maximoAdjuntosTramite = 5,
        public int $adjuntoMaximoMb = 5,
        public int $reciboMaximoMb = 5,
        public int $fotoMaximoMb = 2,
        public int $fotoLadoMinimoPx = 200,
        public int $fotoLadoMaximoPx = 3000,
        public int $umbralPocasVacantes = 5,
    ) {}

    /**
     * @param  array<string, int>  $valores  keyed by the Parametro column
     */
    public static function desde(array $valores): self
    {
        $valor = fn (Parametro $parametro): int => $valores[$parametro->value] ?? $parametro->porDefecto();

        return new self(
            diasVigenciaClasificacion: $valor(Parametro::DiasVigenciaClasificacion),
            puntajeMaximoClasificacion: $valor(Parametro::PuntajeMaximoClasificacion),
            inicioAbreMinutosAntes: $valor(Parametro::InicioAbreMinutosAntes),
            cierreAbreMinutosAntesDelFin: $valor(Parametro::CierreAbreMinutosAntesDelFin),
            cierreCierraMinutosDespuesDelFin: $valor(Parametro::CierreCierraMinutosDespuesDelFin),
            confirmacionMinutosDespuesDelFin: $valor(Parametro::ConfirmacionMinutosDespuesDelFin),
            maximoAdjuntosTramite: $valor(Parametro::MaximoAdjuntosTramite),
            adjuntoMaximoMb: $valor(Parametro::AdjuntoMaximoMb),
            reciboMaximoMb: $valor(Parametro::ReciboMaximoMb),
            fotoMaximoMb: $valor(Parametro::FotoMaximoMb),
            fotoLadoMinimoPx: $valor(Parametro::FotoLadoMinimoPx),
            fotoLadoMaximoPx: $valor(Parametro::FotoLadoMaximoPx),
            umbralPocasVacantes: $valor(Parametro::UmbralPocasVacantes),
        );
    }

    /**
     * @return array<string, int>
     */
    public function comoArreglo(): array
    {
        return [
            Parametro::DiasVigenciaClasificacion->value => $this->diasVigenciaClasificacion,
            Parametro::PuntajeMaximoClasificacion->value => $this->puntajeMaximoClasificacion,
            Parametro::InicioAbreMinutosAntes->value => $this->inicioAbreMinutosAntes,
            Parametro::CierreAbreMinutosAntesDelFin->value => $this->cierreAbreMinutosAntesDelFin,
            Parametro::CierreCierraMinutosDespuesDelFin->value => $this->cierreCierraMinutosDespuesDelFin,
            Parametro::ConfirmacionMinutosDespuesDelFin->value => $this->confirmacionMinutosDespuesDelFin,
            Parametro::MaximoAdjuntosTramite->value => $this->maximoAdjuntosTramite,
            Parametro::AdjuntoMaximoMb->value => $this->adjuntoMaximoMb,
            Parametro::ReciboMaximoMb->value => $this->reciboMaximoMb,
            Parametro::FotoMaximoMb->value => $this->fotoMaximoMb,
            Parametro::FotoLadoMinimoPx->value => $this->fotoLadoMinimoPx,
            Parametro::FotoLadoMaximoPx->value => $this->fotoLadoMaximoPx,
            Parametro::UmbralPocasVacantes->value => $this->umbralPocasVacantes,
        ];
    }

    public function adjuntoMaximoKb(): int
    {
        return $this->adjuntoMaximoMb * 1024;
    }

    public function reciboMaximoKb(): int
    {
        return $this->reciboMaximoMb * 1024;
    }

    public function fotoMaximoKb(): int
    {
        return $this->fotoMaximoMb * 1024;
    }
}
