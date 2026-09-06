<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\StudentClass;
use App\Notifications\AttendanceDeadlineNotification;
use Illuminate\Console\Command;

final class CheckAttendanceDeadline extends Command
{
    protected $signature = 'attendance:check-deadline';

    protected $description = 'Notify admins about classes whose attendance has not been marked by the 8:20 AM deadline';

    public function handle(): int
    {
        $pendingClasses = StudentClass::query()
            ->whereHas('students', fn ($query) => $query->active())
            ->whereDoesntHave('attendances', fn ($query) => $query->whereDate('date', today()))
            ->with('students')
            ->get();

        if ($pendingClasses->isEmpty()) {
            $this->info('All classes have submitted attendance for today.');

            return self::SUCCESS;
        }

        $admins = Admin::all();

        if ($admins->isEmpty()) {
            $this->warn('No admins exist to notify.');

            return self::SUCCESS;
        }

        foreach ($pendingClasses as $class) {
            foreach ($admins as $admin) {
                $admin->notify(new AttendanceDeadlineNotification($class));
                $this->line(sprintf('Notified %s about class "%s".', $admin->email, $class->name));
            }
        }

        return self::SUCCESS;
    }
}
