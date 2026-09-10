<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Staff assignments and their approval workflow.
 *
 * A class + subject pair can only ever be taught by one teacher, so
 * staff_class_subject carries both the (staff, class, subject) uniqueness
 * and the (class, subject) uniqueness. Requests let staff propose
 * assignments or release them, reviewed by an admin.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_class_subject')) {
            Schema::create('staff_class_subject', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
                $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
                $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['staff_id', 'student_class_id', 'subject_id']);
                $table->unique(['student_class_id', 'subject_id'], 'staff_class_subject_class_subject_unique');
            });
        }

        if (! Schema::hasTable('staff_assignment_requests')) {
            Schema::create('staff_assignment_requests', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
                $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
                $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignUuid('target_staff_assignment_id')->nullable()->constrained('staff_class_subject')->nullOnDelete();
                $table->string('action');
                $table->text('reason')->nullable();
                $table->string('status')->default('pending');
                $table->foreignUuid('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_note')->nullable();
                $table->timestamps();
            });
        }
    }
};
