<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

const REQUISITO_ANTERIOR = 'Los cuatro ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

const REQUISITO_ACTUAL = 'Todos los ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

function migracionDeRequisitosDelCertificado(): Migration
{
    return require base_path('app/Modules/Tramites/Database/Migrations/2026_11_03_120000_reword_level_certificate_requirement.php');
}

function requisitosDelCertificado(): string
{
    return (string) DB::table('tra_tipos_tramite')->where('codigo', 'CERTIFICADO-NIVEL')->value('requisitos');
}

it('says that every cycle of the level is required, not four', function (): void {
    expect(requisitosDelCertificado())->toBe(REQUISITO_ACTUAL);

    migracionDeRequisitosDelCertificado()->down();

    expect(requisitosDelCertificado())->toBe(REQUISITO_ANTERIOR);

    migracionDeRequisitosDelCertificado()->up();

    expect(requisitosDelCertificado())->toBe(REQUISITO_ACTUAL);
});

it('keeps a requirement the CILN already rewrote, in both directions', function (): void {
    DB::table('tra_tipos_tramite')->where('codigo', 'CERTIFICADO-NIVEL')->update(['requisitos' => 'Texto del CILN.']);

    migracionDeRequisitosDelCertificado()->down();
    expect(requisitosDelCertificado())->toBe('Texto del CILN.');

    migracionDeRequisitosDelCertificado()->up();
    expect(requisitosDelCertificado())->toBe('Texto del CILN.');
});
