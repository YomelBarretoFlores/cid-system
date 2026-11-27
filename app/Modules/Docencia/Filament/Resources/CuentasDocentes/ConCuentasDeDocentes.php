<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CuentasDocentes;

use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Data\CuentaDelPortal;

// The pages that show teacher accounts (the accounts list and the teacher page) read them once per render.
interface ConCuentasDeDocentes
{
    public function cuentaDe(Docente $docente): ?CuentaDelPortal;

    // After a change the next render must read the accounts again.
    public function olvidarCuentas(): void;
}
