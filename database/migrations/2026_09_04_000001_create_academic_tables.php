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
        Schema::create('student_classes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_subject', function (Blueprint $table): void {
            $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->primary(['student_class_id', 'subject_id']);
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('sr_no')->nullable()->unique();
            $table->string('name');
            $table->foreignUuid('student_class_id')->constrained('student_classes')->restrictOnDelete();
            $table->date('joining_date');
            $table->date('leaving_date')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('class_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('student_classes');
    }
};
