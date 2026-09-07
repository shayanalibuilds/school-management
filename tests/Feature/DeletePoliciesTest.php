<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

/**
 * The school-wide rule: no record can ever be deleted. Every model's
 * policy denies the whole delete family (delete, deleteAny,
 * forceDelete, forceDeleteAny) to every user — archiving is the only
 * way a record ever "leaves". Everything a panel needs for daily work
 * (view, create, update) stays allowed, so the policies must also
 * exist and resolve for every model: a policy with missing methods
 * would silently deny those too.
 */
const SCHOOL_RECORD_MODELS = [
    Admin::class,
    App\Models\Attendance::class,
    App\Models\ExamResult::class,
    App\Models\Expense::class,
    App\Models\Fee::class,
    App\Models\FeeStructure::class,
    App\Models\Guardian::class,
    App\Models\Payment::class,
    App\Models\PaymentSetting::class,
    App\Models\Payroll::class,
    Staff::class,
    App\Models\StaffAssignment::class,
    App\Models\StaffAssignmentRequest::class,
    App\Models\Student::class,
    App\Models\StudentClass::class,
    App\Models\StudentParent::class,
    App\Models\Subject::class,
    App\Models\TimetableSlot::class,
];

const DENIED_ABILITIES = ['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny'];

const ALLOWED_ABILITIES = ['viewAny', 'view', 'create', 'update'];

it('resolves a policy for every school record model', function (): void {
    foreach (SCHOOL_RECORD_MODELS as $model) {
        $policy = Gate::getPolicyFor($model);

        expect($policy)->not->toBeNull("missing policy for {$model}");

        $policyClass = is_object($policy) ? $policy::class : '';

        expect($policyClass)->toEndWith('Policy');
    }
});

it('denies every delete ability to admins and staff on every record', function (): void {
    $admin = Admin::factory()->create();
    $staff = Staff::factory()->create();

    foreach ([$admin, $staff] as $user) {
        foreach (SCHOOL_RECORD_MODELS as $model) {
            $record = new $model();

            foreach (DENIED_ABILITIES as $ability) {
                $allowed = Gate::forUser($user)->allows($ability, $record);

                expect($allowed)->toBeFalse(
                    "{$ability} on {$model} must be denied — nothing is ever deleted",
                );
            }
        }
    }
});

it('keeps daily work allowed so the panels stay functional', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');

    foreach (SCHOOL_RECORD_MODELS as $model) {
        $record = new $model();

        foreach (ALLOWED_ABILITIES as $ability) {
            $allowed = Gate::forUser($admin)->allows($ability, $record);

            expect($allowed)->toBeTrue(
                "{$ability} on {$model} must stay allowed for panel users",
            );
        }
    }
});
