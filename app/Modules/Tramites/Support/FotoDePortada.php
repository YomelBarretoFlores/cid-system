<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FotoDePortada
{
    public const string DISCO = 'public';

    public const int MAXIMO_KB = 5120;

    public const array EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function url(?string $ruta): string
    {
        return $ruta === null ? asset('images/portada/portada.jpg') : Storage::disk(self::DISCO)->url($ruta);
    }

    /**
     * The photo the row keeps: a new upload is stored, an empty value drops the photo and anything else keeps the current one.
     */
    public static function elegida(mixed $valor, ?string $actual): ?string
    {
        return match (true) {
            $valor instanceof UploadedFile => self::guardar($valor),
            $valor === null, $valor === '', $valor === [] => null,
            default => $actual,
        };
    }

    public static function borrar(?string $ruta): void
    {
        if ($ruta !== null) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    private static function guardar(UploadedFile $foto): string
    {
        $tipo = (string) new finfo(FILEINFO_MIME_TYPE)->file($foto->getRealPath());
        throw_unless(array_key_exists($tipo, self::EXTENSIONES), ReglaDeNegocioIncumplida::class, 'La foto de portada debe ser una imagen JPG, PNG o WebP.');
        throw_if(($foto->getSize() ?: 0) > self::MAXIMO_KB * 1024, ReglaDeNegocioIncumplida::class, 'La foto de portada debe pesar hasta '.(self::MAXIMO_KB / 1024).' MB.');

        $ruta = Storage::disk(self::DISCO)->putFileAs('portada', $foto, Str::ulid().'.'.self::EXTENSIONES[$tipo]);
        throw_if($ruta === false, ReglaDeNegocioIncumplida::class, 'No pudimos guardar la foto de portada. Inténtalo de nuevo.');

        return $ruta;
    }
}
