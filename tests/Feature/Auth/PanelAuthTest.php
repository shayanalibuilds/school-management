<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected from the admin panel to its login', function (): void {
    get('/dashboard')->assertRedirect('/dashboard/login');
});

test('admins can view the admin panel', function (): void {
    actingAs(Admin::factory()->create(), 'admin')
        ->get('/dashboard')
        ->assertOk();
});

test('staff members cannot enter the admin panel', function (): void {
    actingAs(Staff::factory()->create(), 'staff')
        ->get('/dashboard')
        ->assertRedirect();
});

test('guests are redirected from the staff panel to its login', function (): void {
    get('/staff')->assertRedirect('/staff/login');
});

test('staff members can view the staff panel', function (): void {
    actingAs(Staff::factory()->create(), 'staff')
        ->get('/staff')
        ->assertOk();
});

test('admins cannot enter the staff panel', function (): void {
    actingAs(Admin::factory()->create(), 'admin')
        ->get('/staff')
        ->assertRedirect();
});
