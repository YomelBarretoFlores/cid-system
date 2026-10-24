<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The package's primary key starts with permission_id, so lookups and cascades by role scan the table.
    public function up(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table): void {
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('role_has_permissions', function (Blueprint $table): void {
            $table->dropIndex(['role_id']);
        });
    }
};
