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
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
            $table->string('month', 7);
            $table->decimal('amount', 10, 2);
            $table->string('status')->nullable()->default('active');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'month']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('recurrence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payrolls');
    }
};
