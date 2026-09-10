<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Attendance and notifications.
 *
 * Attendance can be recorded by either panel: staff members sign with
 * staff_id, admins sign with admin_id; exactly one is set per record.
 * The notifiable morph is UUID-typed because every model in the app uses
 * UUID primary keys (HasUuids).
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->uuidMorphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
                $table->foreignUuid('staff_id')->nullable()->constrained('staffs')->nullOnDelete();
                $table->foreignUuid('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->date('date');
                $table->string('status')->default('present');
                $table->timestamps();
                $table->unique(['student_id', 'date']);
                $table->index(['student_class_id', 'date']);
            });
        }
    }
};
