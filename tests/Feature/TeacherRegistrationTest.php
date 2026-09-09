<?php

declare(strict_types=1);

use App\Enums\StaffStatus;
use App\Filament\Staff\Pages\Auth\RegisterTeacher;
use App\Models\Staff;
use App\Models\Teacher;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('greets guests with the cnic step only', function (): void {
    get('/staff/register')
        ->assertOk()
        ->assertSee('CNIC')
        ->assertDontSee('Password');
});

it('hides the account form when the cnic is not on the roster', function (): void {
    Livewire::test(RegisterTeacher::class)
        ->fillForm(['cnic' => '99999-9999999-9'])
        ->call('verifyCnic')
        ->assertSet('verifiedTeacherId', null);

    expect(Staff::count())->toBe(0);
});

it('hides the account form when the cnic already registered', function (): void {
    $teacher = Teacher::factory()->create(['cnic' => '35202-1111111-1']);
    Staff::factory()->create(['teacher_id' => $teacher->id, 'cnic' => $teacher->cnic]);

    Livewire::test(RegisterTeacher::class)
        ->fillForm(['cnic' => $teacher->cnic])
        ->call('verifyCnic')
        ->assertSet('verifiedTeacherId', null);

    expect(Staff::count())->toBe(1);
});

it('walks a roster teacher through registration', function (): void {
    // Mirror the real request: the page lives on the staff panel, so the
    // panel's own guard is the one the new account is logged into.
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    $teacher = Teacher::factory()->create(['name' => 'Ayesha Khan', 'cnic' => '35202-1234567-1']);

    Livewire::test(RegisterTeacher::class)
        ->fillForm(['cnic' => $teacher->cnic])
        ->call('verifyCnic')
        ->assertSet('verifiedTeacherId', $teacher->id)
        ->assertSet('data.name', 'Ayesha Khan')
        ->fillForm([
            'email' => 'ayesha@school.test',
            'password' => 'super-secret-123',
            'passwordConfirmation' => 'super-secret-123',
        ])
        ->call('register');

    $staff = Staff::query()->where('email', 'ayesha@school.test')->firstOrFail();

    expect($staff->name)->toBe('Ayesha Khan')
        ->and($staff->cnic)->toBe($teacher->cnic)
        ->and($staff->teacher_id)->toBe($teacher->id)
        ->and(auth('staff')->check())->toBeTrue()
        ->and($staff->status)->toBe(StaffStatus::Active)
        ->and($staff->getAttributes()['joining_date'])->toBe(today()->toDateTimeString());
});

it('ignores a forged name sent alongside a real registration', function (): void {
    $teacher = Teacher::factory()->create(['name' => 'Roster Name', 'cnic' => '35202-7654321-1']);

    Livewire::test(RegisterTeacher::class)
        ->fillForm(['cnic' => $teacher->cnic])
        ->call('verifyCnic')
        ->fillForm([
            'name' => 'Forged Name',
            'email' => 'forge@school.test',
            'password' => 'super-secret-123',
            'passwordConfirmation' => 'super-secret-123',
        ])
        ->call('register');

    expect(Staff::query()->where('email', 'forge@school.test')->firstOrFail()->name)->toBe('Roster Name');
});

it('blocks a register call that skipped the cnic check', function (): void {
    Livewire::test(RegisterTeacher::class)
        ->fillForm([
            'email' => 'ghost@school.test',
            'password' => 'super-secret-123',
            'passwordConfirmation' => 'super-secret-123',
        ])
        ->call('register');

    expect(Staff::count())->toBe(0);
});

it('rate limits cnic lookups per ip', function (): void {
    foreach (range(1, 5) as $attempt) {
        Livewire::test(RegisterTeacher::class)
            ->fillForm(['cnic' => '00000-'.mb_str_pad((string) $attempt, 7, '0', STR_PAD_LEFT).'-0'])
            ->call('verifyCnic');
    }

    $key = 'livewire-rate-limiter:'.sha1(RegisterTeacher::class.'|cnic-lookup|'.request()->ip());

    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

    // The sixth lookup - even with a valid CNIC - is refused before it
    // reaches the roster.
    $teacher = Teacher::factory()->create(['cnic' => '35202-9999999-9']);

    Livewire::test(RegisterTeacher::class)
        ->fillForm(['cnic' => $teacher->cnic])
        ->call('verifyCnic')
        ->assertSet('verifiedTeacherId', null);
});
