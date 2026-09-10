<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Accounts and the teaching roster.
 *
 * The teachers table is the school's roster of teaching staff, managed by
 * the admin. A teacher first exists here (name + CNIC) and only gains a
 * sign-in account after proving that CNIC on the staff registration page,
 * which links staffs.teacher_id to the roster entry. Created together with
 * the account tables so the foreign key exists from day one.
 *
 * Status columns are NOT NULL with a domain-correct default: a record must
 * always have a status. Forward-only and idempotent — no drop methods.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('cnic')->unique();
                $table->string('phone', 20)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('staffs')) {
            Schema::create('staffs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('teacher_id')
                    ->nullable()
                    ->unique()
                    ->constrained('teachers')
                    ->nullOnDelete();
                $table->string('name');
                $table->string('cnic')->unique();
                $table->string('email')->nullable()->unique();
                $table->string('password')->nullable();
                $table->string('phone', 20)->nullable();
                $table->date('joining_date');
                $table->date('leaving_date')->nullable();
                $table->string('status')->default('active');
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
};
