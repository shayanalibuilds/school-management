<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Parents and guardians, each linked to their students.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parents')) {
            Schema::create('parents', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('cnic')->unique();
                $table->string('phone', 20);
                $table->string('occupation')->nullable();
                $table->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted (parents)");
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('guardians')) {
            Schema::create('guardians', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('cnic')->unique();
                $table->string('phone', 20);
                $table->string('relation')->nullable();
                $table->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted (guardians)");
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('parent_student')) {
            Schema::create('parent_student', function (Blueprint $table): void {
                $table->foreignUuid('parent_id')->constrained('parents')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->primary(['parent_id', 'student_id']);
            });
        }

        if (! Schema::hasTable('guardian_student')) {
            Schema::create('guardian_student', function (Blueprint $table): void {
                $table->foreignUuid('guardian_id')->constrained('guardians')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->primary(['guardian_id', 'student_id']);
            });
        }
    }
};
