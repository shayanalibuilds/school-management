<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Older builds stored the attendance date in mixed formats
 * ('2026-09-06' vs '2026-09-06 00:00:00'). Because the unique index
 * compares raw strings, a mixed-history table could hold two rows for
 * the same student and day. Normalise every row to the canonical
 * datetime shape the model writes today so one student + one day is
 * always exactly one row, and the fill-attendance upsert never trips
 * the unique constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "UPDATE attendances SET date = strftime('%Y-%m-%d 00:00:00', date) WHERE date IS NOT NULL AND date <> strftime('%Y-%m-%d 00:00:00', date)",
        );
    }

    public function down(): void
    {
        // The mixed formats are lossy history, not a state worth
        // returning to; normalised rows stay as they are.
    }
};
