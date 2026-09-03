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
        Schema::create('parents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('phone', 20);
            $table->string('occupation')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('guardians', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('phone', 20);
            $table->string('relation')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_student', function (Blueprint $table): void {
            $table->foreignUuid('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->primary(['parent_id', 'student_id']);
        });

        Schema::create('guardian_student', function (Blueprint $table): void {
            $table->foreignUuid('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->primary(['guardian_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('parents');
    }
};
