<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Controllers;

use App\Modules\Personas\Http\Controllers\Concerns\SirveFoto;
use App\Modules\Personas\Models\Persona;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Takes no id at all, so a student can only ever reach their own photo.
final class VerFotoPropia
{
    use SirveFoto;

    public function __invoke(Request $request): StreamedResponse
    {
        $foto = Persona::query()->where('user_id', $request->user()?->getAuthIdentifier())->value('foto');

        return $this->servirFoto(is_string($foto) ? $foto : null);
    }
}
