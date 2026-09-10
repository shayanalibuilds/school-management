<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Exam results.
 *
 * Draft results are only visible to staff; published results become
 * visible to students and open a 30-day correction window, after which
 * they are locked. The status column starts every result as 'draft'.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_results')) {
            return;
        }

        Schema::create('exam_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('student_class_id')->constrained('student_classes')->restrictOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->unsignedInteger('year');
            $table->decimal('marks', 6, 2);
            $table->decimal('total_marks', 6, 2)->default(100);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'subject_id', 'year']);
            $table->index(['student_class_id', 'year']);
        });
    }
};
