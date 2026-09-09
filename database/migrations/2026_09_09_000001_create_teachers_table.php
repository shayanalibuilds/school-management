<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The teachers table is the school's roster of teaching staff, managed by
 * the admin. A teacher first exists here (name + CNIC) and only gains a
 * sign-in account after proving that CNIC on the staff registration page.
 * Every existing staff account is backfilled into the roster so the two
 * tables stay in sync from day one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('cnic')->unique();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('staffs', function (Blueprint $table): void {
            $table->uuid('teacher_id')->nullable()->unique()->after('id');
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->nullOnDelete();
        });

        foreach (DB::table('staffs')->whereNull('deleted_at')->get() as $staff) {
            $teacherId = Str::uuid()->toString();

            DB::table('teachers')->insert([
                'id' => $teacherId,
                'name' => $staff->name,
                'cnic' => $staff->cnic,
                'phone' => $staff->phone,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('staffs')
                ->where('id', $staff->id)
                ->update(['teacher_id' => $teacherId]);
        }
    }

    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table): void {
            $table->dropForeign(['teacher_id']);
            $table->dropColumn('teacher_id');
        });

        Schema::dropIfExists('teachers');
    }
};
