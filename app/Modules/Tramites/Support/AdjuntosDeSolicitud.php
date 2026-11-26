<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// The real MIME type is PDF, JPG or PNG, within the size set in «Parámetros del sistema», kept on the private disk.
final class AdjuntosDeSolicitud
{
    public const string DISCO = 'local';

    private const array EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    public function __construct(private ParametrosDelSistema $parametrosDelSistema) {}

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return list<ArchivoAdjunto>
     */
    public function validar(TipoTramite $tipo, array $archivos): array
    {
        $preparados = [];

        foreach ($tipo->adjuntos as $indice => $pedido) {
            $archivo = $archivos[$indice] ?? null;

            if ($archivo instanceof UploadedFile) {
                $preparados[] = $this->preparar($pedido['nombre'], $archivo);
            } elseif ($pedido['obligatorio']) {
                throw new ReglaDeNegocioIncumplida("Adjunta el archivo «{$pedido['nombre']}».");
            }
        }

        return $preparados;
    }

    /**
     * @param  list<ArchivoAdjunto>  $archivos
     * @param  list<string>  $rutas  receives every stored path, so the caller can remove them if the request fails
     */
    public function guardar(Tramite $tramite, array $archivos, array &$rutas): void
    {
        foreach ($archivos as $adjunto) {
            $ruta = (string) $adjunto->archivo->storeAs('tramites/'.now('America/Lima')->format('Y'), Str::ulid().".{$adjunto->extension}", self::DISCO);
            $rutas[] = $ruta;

            $tramite->adjuntos()->create([
                'nombre' => $adjunto->nombre,
                'path' => $ruta,
                'sha256' => (string) hash_file('sha256', (string) $adjunto->archivo->getRealPath()),
                'bytes' => (int) $adjunto->archivo->getSize(),
                'mime' => $adjunto->mime,
            ]);
        }
    }

    /**
     * @param  list<string>  $rutas
     */
    public function descartar(array $rutas): void
    {
        if ($rutas !== []) {
            Storage::disk(self::DISCO)->delete($rutas);
        }
    }

    private function preparar(string $nombre, UploadedFile $archivo): ArchivoAdjunto
    {
        $mime = (string) new finfo(FILEINFO_MIME_TYPE)->file((string) $archivo->getRealPath());
        $extension = self::EXTENSIONES[$mime] ?? throw new ReglaDeNegocioIncumplida("El adjunto «{$nombre}» debe ser un PDF o una imagen JPG o PNG.");

        $parametros = ($this->parametrosDelSistema)();
        throw_if((int) $archivo->getSize() > $parametros->adjuntoMaximoKb() * 1024, ReglaDeNegocioIncumplida::class, "El adjunto «{$nombre}» no debe pesar más de {$parametros->adjuntoMaximoMb} MB.");

        return new ArchivoAdjunto($nombre, $archivo, $mime, $extension);
    }
}
