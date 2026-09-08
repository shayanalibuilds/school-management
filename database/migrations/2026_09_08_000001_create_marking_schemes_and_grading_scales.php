<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Admin-defined mark limits per class and subject. Without a row
        // the default bounds (0 - 100) apply, matching the legacy sheets.
        Schema::create('marking_schemes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_class_id')->constrained('student_classes')->restrictOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->decimal('min_marks', 6, 2)->default(0);
            $table->decimal('max_marks', 6, 2)->default(100);
            $table->timestamps();
            $table->unique(['student_class_id', 'subject_id']);
        });

        // Admin-defined grade boundaries (percentage thresholds). Without
        // rows the built-in boundaries (A+ 90, A 80, ...) apply.
        Schema::create('grading_scales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 16);
            $table->decimal('min_percentage', 5, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
        Schema::dropIfExists('marking_schemes');
    }
};
