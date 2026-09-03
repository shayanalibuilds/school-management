<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Admin::factory()->create([
            'name' => 'School Admin',
            'email' => 'admin@school.test',
            'password' => 'password',
        ]);

        Staff::factory()->create([
            'name' => 'Demo Teacher',
            'email' => 'teacher@school.test',
            'password' => 'password',
        ]);
    }
}
