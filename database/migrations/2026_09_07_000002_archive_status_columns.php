<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School records are archived, never deleted: the delete flow flips a
 * status instead of removing rows. Classes, subjects, parents,
 * guardians and fee structures get an explicit status column here;
 * students already carry one (active / graduated / left).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['student_classes', 'subjects', 'parents', 'guardians', 'fee_structures'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted ({$table})");
            });
        }
    }

    public function down(): void
    {
        foreach (['student_classes', 'subjects', 'parents', 'guardians', 'fee_structures'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropIndex(['status']);
                $blueprint->dropColumn('status');
            });
        }
    }
};
