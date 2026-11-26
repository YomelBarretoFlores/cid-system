<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The catalog decides each type's length and digits; whatever the type, a stored number is upper-case letters and digits only.
    // NOT VALID: new and edited rows are checked, while a legacy row on a production copy cannot block the update.
    public function up(): void
    {
        DB::statement("ALTER TABLE per_personas ADD CONSTRAINT per_personas_numero_documento_formato_check CHECK (numero_documento ~ '^[A-Z0-9]{1,20}$') NOT VALID");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE per_personas DROP CONSTRAINT per_personas_numero_documento_formato_check');
    }
};
