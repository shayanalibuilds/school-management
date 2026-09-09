<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\StaffStatus;
use App\Models\Staff;
use App\Models\Teacher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class StaffImporter extends Importer
{
    protected static ?string $model = Staff::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('cnic')
                ->label('CNIC')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('email')
                ->rules(['nullable', 'email', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['nullable', 'max:30']),
            ImportColumn::make('joining_date')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:active,on_leave,resigned']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $successful = (int) $import->successful_rows;
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount === 0) {
            return self::countedNoun($successful, 'staff').' imported.';
        }

        return self::countedNoun($successful, 'staff').' imported, '.self::countedNoun($failedRowsCount, 'row')
            .' failed - use the download button to see why each one was rejected.';
    }

    /**
     * Staff are matched by their CNIC, the one identifier every staff
     * member already has on record.
     */
    public function resolveRecord(): ?Staff
    {
        $cnic = mb_trim((string) ($this->data['cnic'] ?? ''));

        if ($cnic === '') {
            return null;
        }

        return Staff::query()->firstOrNew(['cnic' => $cnic]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        if (! $record instanceof Staff) {
            return;
        }

        $rosterName = mb_trim((string) ($this->data['name'] ?? $record->name));

        // The teacher roster owns names: imported name changes are filed
        // into the roster and flow into the staff account through its sync,
        // exactly like an edit made from the roster screen.
        $teacher = $record->teacher;

        if ($teacher === null) {
            $teacher = Teacher::query()->firstOrNew(['cnic' => $record->cnic]);
            $teacher->name = $rosterName;
            $teacher->save();
            $record->teacher_id = $teacher->id;

            // A brand-new row is saved before the roster sync can observe
            // it, so mirror the name onto the account directly.
            $record->name = $teacher->name;
        } elseif ($teacher->name !== $rosterName) {
            $teacher->name = $rosterName;
            $teacher->save();
        }

        $record->email = filled($this->data['email'] ?? null)
            ? mb_trim((string) $this->data['email'])
            : $record->email;
        $record->phone = filled($this->data['phone'] ?? null)
            ? mb_trim((string) $this->data['phone'])
            : $record->phone;
        $joiningDate = $this->data['joining_date'] ?? null;
        $record->joining_date = filled($joiningDate) && is_string($joiningDate)
            ? $joiningDate
            : $record->joining_date;

        $status = StaffStatus::tryFrom((string) ($this->data['status'] ?? ''));

        if ($status !== null) {
            $record->status = $status->value;
        } elseif (! $record->exists) {
            // A brand-new row with no status column starts active.
            $record->status = StaffStatus::Active->value;
        }
    }
}
