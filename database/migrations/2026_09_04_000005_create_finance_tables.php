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
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fees', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fee_structure_id')->constrained('fee_structures')->restrictOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->nullable()->default('active');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'year']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('fee_id')->constrained('fees')->cascadeOnDelete();
            $table->string('provider');
            $table->string('payer_name')->nullable();
            $table->string('payer_cnic')->nullable();
            $table->string('payer_phone')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable()->unique();
            $table->string('status')->nullable()->default('active');
            $table->timestamp('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider')->unique();
            $table->string('environment')->default('sandbox');
            $table->boolean('is_active')->default(false);
            $table->text('credentials');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('fee_structures');
    }
};
