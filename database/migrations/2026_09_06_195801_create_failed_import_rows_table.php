<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Rows that failed validation during an import, kept for review.
 * Forward-only and idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_import_rows')) {
            return;
        }

        Schema::create('failed_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->json('data');
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->text('validation_error')->nullable();
            $table->timestamps();
        });
    }
};
