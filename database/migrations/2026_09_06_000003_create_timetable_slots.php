<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Weekly timetable slots.
 *
 * The one hard rule at the database level: a class has at most one
 * subject in a given time slot. Schools have multiple rooms, so the
 * same subject CAN be taught to several classes at the same time (by
 * different teachers) — a subject-wide unique index is deliberately
 * NOT created (it would also collide with the subject foreign key's
 * required index on MariaDB). A teacher cannot be in two classes at
 * once, and overlapping (but not identical) time ranges are validated
 * in the form via TimetableSlot::findConflict().
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timetable_slots')) {
            return;
        }

        Schema::create('timetable_slots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_class_id')->constrained('student_classes')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1 = Monday ... 7 = Sunday');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();

            $table->unique(['student_class_id', 'day_of_week', 'start_time'], 'timetable_slots_class_slot_unique');
            $table->index(['day_of_week', 'start_time']);
        });
    }
};
