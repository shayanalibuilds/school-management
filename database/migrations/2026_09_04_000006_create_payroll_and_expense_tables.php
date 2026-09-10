<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Staff payroll and school expenses.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('staff_id')->constrained('staffs')->cascadeOnDelete();
                $table->string('month', 7);
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->unique(['staff_id', 'month']);
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('recurrence');
                $table->timestamps();
            });
        }
    }
};
