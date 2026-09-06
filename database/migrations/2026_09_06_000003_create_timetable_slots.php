<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Weekly timetable slots. Two hard rules live at the database level:
     * - a class has at most one subject in a given time slot
     * - a subject appears in at most one class in a given time slot
     * Overlapping (but not identical) time ranges are additionally
     * validated in the form.
     */
    public function up(): void
    {
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
            $table->unique(['subject_id', 'day_of_week', 'start_time'], 'timetable_slots_subject_slot_unique');
            $table->index(['day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
