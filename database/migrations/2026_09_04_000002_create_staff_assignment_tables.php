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
        Schema::create('staff_class_subject', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
            $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['staff_id', 'student_class_id', 'subject_id']);
        });

        Schema::create('staff_assignment_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
            $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignUuid('target_staff_assignment_id')->nullable()->constrained('staff_class_subject')->nullOnDelete();
            $table->string('action');
            $table->text('reason')->nullable();
            $table->string('status');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_assignment_requests');
        Schema::dropIfExists('staff_class_subject');
    }
};
