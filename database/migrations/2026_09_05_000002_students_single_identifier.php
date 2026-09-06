<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Students carry exactly one identifier: the GR #.
     * The serial number is gone, and parent information lives in the
     * parents table through its relation, not as columns on students.
     */
    public function up(): void
    {
        // Guarantee every student has a GR # before anything is dropped.
        DB::table('students')
            ->whereNull('gr_no')
            ->update(['gr_no' => DB::raw("'GR-' || upper(substr(id, 1, 8))")]);

        // SQLite keeps a named index alive after its column is gone and
        // then fails the drop, so the index has to be dropped first.
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_sr_no_unique');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['sr_no', 'father_name']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('father_name')->nullable()->after('name');
            $table->unsignedInteger('sr_no')->nullable()->after('name');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->unique('sr_no');
        });
    }
};
