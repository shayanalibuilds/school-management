<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('gr_no')->nullable()->unique()->after('sr_no');
            $table->string('father_name')->nullable()->after('name');
            $table->date('date_of_birth')->nullable()->after('father_name');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('b_form_cnic')->nullable()->after('gender');
            $table->string('phone', 20)->nullable()->after('b_form_cnic');
            $table->string('previous_school')->nullable()->after('phone');
            $table->text('address')->nullable()->after('previous_school');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn([
                'gr_no',
                'father_name',
                'date_of_birth',
                'gender',
                'b_form_cnic',
                'phone',
                'previous_school',
                'address',
            ]);
        });
    }
};
