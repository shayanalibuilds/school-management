<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Simple key/value store for app-wide feature toggles
 * (see App\Support\AppSettings). Forward-only and idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_settings')) {
            return;
        }

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('value');
            $table->timestamps();
        });
    }
};
