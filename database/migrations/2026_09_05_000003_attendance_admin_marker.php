<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attendance can be recorded by either panel: staff members sign with
     * staff_id, admins sign with admin_id. Exactly one is set per record.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignUuid('admin_id')->nullable()->after('staff_id')->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admin_id');
        });
    }
};
