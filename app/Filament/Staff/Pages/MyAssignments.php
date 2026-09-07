<?php

declare(strict_types=1);

namespace App\Filament\Staff\Pages;

use App\Models\Staff;
use BackedEnum;
use Filament\Pages\Page;

final class MyAssignments extends Page
{
    protected string $view = 'filament.staff.pages.my-assignments';

    protected ?string $heading = 'My assignments';

    protected static ?string $navigationLabel = 'My Assignments';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedBookOpen;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            return ['assignments' => collect()];
        }

        return [
            'assignments' => $staff->assignments()->with(['studentClass', 'subject'])->orderBy('student_class_id')->get(),
        ];
    }
}
