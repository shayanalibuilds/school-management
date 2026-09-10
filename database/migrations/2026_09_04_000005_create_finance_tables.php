<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Finance: fee structures, fees, payments and gateway settings.
 *
 * Forward-only and idempotent — no drop methods anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_structures')) {
            Schema::create('fee_structures', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('type');
                $table->decimal('amount', 10, 2);
                $table->string('status')->default('active')->index()->comment("Archived records are 'inactive' - rows are never deleted (fee_structures)");
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('fees')) {
            Schema::create('fees', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('fee_structure_id')->constrained('fee_structures')->restrictOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->unsignedInteger('year');
                $table->decimal('amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->string('status')->default('unpaid');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['student_id', 'year']);
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('fee_id')->constrained('fees')->cascadeOnDelete();
                $table->string('provider');
                $table->string('payer_name')->nullable();
                $table->string('payer_cnic')->nullable();
                $table->string('payer_phone')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('reference')->nullable()->unique();
                $table->string('status')->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->json('gateway_payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_settings')) {
            Schema::create('payment_settings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('provider')->unique();
                $table->string('environment')->default('sandbox');
                $table->boolean('is_active')->default(false);
                $table->text('credentials');
                $table->timestamps();
            });
        }
    }
};
