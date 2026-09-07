<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schools have multiple rooms, so the same subject can be taught to
 * several classes at the same time (by different teachers). Drop the
 * subject-wide slot uniqueness; a class still has at most one subject
 * per slot and a teacher cannot be in two classes at once (both
 * validated in the form via TimetableSlot::findConflict()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table): void {
            $table->dropUnique('timetable_slots_subject_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table): void {
            $table->unique(['subject_id', 'day_of_week', 'start_time'], 'timetable_slots_subject_slot_unique');
        });
    }
};
