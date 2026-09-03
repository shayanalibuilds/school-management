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
        Schema::create('admins', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('staffs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('joining_date');
            $table->date('leaving_date')->nullable();
            $table->string('status');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
        Schema::dropIfExists('admins');
    }
};
