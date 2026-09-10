<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Import runs (Filament imports). Forward-only and idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imports')) {
            return;
        }

        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->uuid('user_id')->nullable()->index();
            $table->timestamps();
        });
    }
};
