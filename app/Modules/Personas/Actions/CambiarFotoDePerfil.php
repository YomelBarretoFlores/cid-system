<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final readonly class CambiarFotoDePerfil
{
    public const string DISCO = 'local';

    private const array EXTENSIONES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public function __invoke(int $personaId, UploadedFile $foto): void
    {
        $ruta = $foto->storeAs('fotos', Str::ulid().'.'.$this->extension($foto), self::DISCO);

        if ($ruta === false) {
            throw new ReglaDeNegocioIncumplida('No pudimos guardar tu fotografía. Inténtalo de nuevo.');
        }

        try {
            $anterior = Transaccion::ejecutar(fn (): ?string => $this->reemplazar($personaId, $ruta));
        } catch (Throwable $error) {
            Storage::disk(self::DISCO)->delete($ruta);

            throw $error;
        }

        // Both run against the caller's transaction when there is one; afterCommit runs at once otherwise.
        DB::afterRollBack(fn (): bool => Storage::disk(self::DISCO)->delete($ruta));
        DB::afterCommit(fn (): bool => $anterior === null || Storage::disk(self::DISCO)->delete($anterior));
    }

    private function reemplazar(int $personaId, string $ruta): ?string
    {
        $persona = Persona::query()->whereKey($personaId)->lockForUpdate()->firstOrFail();
        $anterior = $persona->foto;
        $persona->update(['foto' => $ruta]);

        return $anterior;
    }

    private function extension(UploadedFile $foto): string
    {
        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($foto->getRealPath());

        return self::EXTENSIONES[$tipo]
            ?? throw new ReglaDeNegocioIncumplida('Tu fotografía debe ser una imagen JPG o PNG.');
    }
}
