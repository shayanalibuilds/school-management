<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draft results are only visible to staff; published results become
     * visible to students and open a 30-day correction window. After the
     * window closes the results are locked and can no longer be edited.
     */
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->string('status')->default('draft')->after('total_marks');
            $table->timestamp('published_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->dropColumn(['status', 'published_at']);
        });
    }
};
