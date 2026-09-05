<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every status column a domain-correct default and make it NOT NULL.
     * A record must always have a status; "nullable with a generic default"
     * left room for meaningless states (e.g. a fee whose status is "active").
     */
    public function up(): void
    {
        // Repair rows written while the columns were open to anything.
        $this->remap('staffs', 'active');
        $this->remap('students', 'active');
        $this->remap('staff_assignment_requests', 'pending', ['active']);
        $this->remap('attendances', 'present', ['active']);
        $this->remap('fees', 'unpaid', ['active']);
        $this->remap('payments', 'pending', ['active', 'completed']);
        $this->remap('payrolls', 'pending', ['active']);

        Schema::table('staffs', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });

        Schema::table('staff_assignment_requests', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('status')->default('present')->change();
        });

        Schema::table('fees', function (Blueprint $table): void {
            $table->string('status')->default('unpaid')->change();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('active')->change();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('active')->change();
        });

        Schema::table('staff_assignment_requests', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('pending')->change();
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('present')->change();
        });

        Schema::table('fees', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('unpaid')->change();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('pending')->change();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->string('status')->nullable()->default('pending')->change();
        });
    }

    /**
     * Move rows off values that are not valid for the domain, then close
     * the remaining gaps so the NOT NULL change never fails.
     */
    private function remap(string $table, string $fallback, array $invalid = ['active']): void
    {
        foreach ($invalid as $value) {
            if ($value === $fallback) {
                continue;
            }

            Illuminate\Support\Facades\DB::table($table)
                ->where('status', $value)
                ->update(['status' => $fallback]);
        }

        Illuminate\Support\Facades\DB::table($table)
            ->whereNull('status')
            ->update(['status' => $fallback]);
    }
};
