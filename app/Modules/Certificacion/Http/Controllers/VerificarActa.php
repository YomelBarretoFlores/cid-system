<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Models\Acta;
use Illuminate\Http\Response;

// The page an acta's QR opens: only a signed link reaches it, and it shows that the acta exists and its state, never students or grades (Ley 29733).
final readonly class VerificarActa
{
    public function __invoke(int $acta): Response
    {
        return response()
            ->view('certificacion::verificacion-acta', ['acta' => Acta::query()->withCount('notas')->findOrFail($acta)])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
