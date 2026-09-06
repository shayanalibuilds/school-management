<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A class + subject pair can only ever be taught by one teacher.
     * Existing duplicates (same class + subject under different staff)
     * are removed, keeping the oldest assignment, before the unique
     * index is added.
     */
    public function up(): void
    {
        $duplicates = Illuminate\Support\Facades\DB::table('staff_class_subject')
            ->select('student_class_id', 'subject_id', Illuminate\Support\Facades\DB::raw('MIN(created_at) as keep_created_at'))
            ->groupBy('student_class_id', 'subject_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            Illuminate\Support\Facades\DB::table('staff_class_subject')
                ->where('student_class_id', $duplicate->student_class_id)
                ->where('subject_id', $duplicate->subject_id)
                ->where('created_at', '!=', $duplicate->keep_created_at)
                ->delete();
        }

        Schema::table('staff_class_subject', function (Blueprint $table): void {
            $table->unique(['student_class_id', 'subject_id'], 'staff_class_subject_class_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('staff_class_subject', function (Blueprint $table): void {
            $table->dropUnique('staff_class_subject_class_subject_unique');
        });
    }
};
