<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Academic core: classes, subjects, their link, and students.
 *
 * Students carry exactly one identifier — the GR # — plus their profile
 * columns (date of birth, gender, B-Form CNIC, phone, previous school,
 * address). Serial numbers and father-name columns are deliberately not
 * part of the schema. Records are archived, never deleted: the status
 * column flips to 'inactive' instead.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_classes')) {
            Schema::create('student_classes', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted (student_classes)");
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted (subjects)");
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('class_subject')) {
            Schema::create('class_subject', function (Blueprint $table): void {
                $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
                $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->primary(['student_class_id', 'subject_id']);
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('gr_no')->nullable()->unique();
                $table->date('date_of_birth')->nullable();
                $table->string('gender')->nullable();
                $table->string('b_form_cnic')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('previous_school')->nullable();
                $table->text('address')->nullable();
                $table->foreignUuid('student_class_id')->constrained('student_classes')->restrictOnDelete();
                $table->date('joining_date');
                $table->date('leaving_date')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
};
