<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\StaffStatus;
use App\Models\Staff;
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
        $successful = number_format($import->successful_rows);
        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount === 0) {
            return "Imported {$successful} staff.";
        }

        $failed = number_format($failedRowsCount);

        return "Imported {$successful} staff. {$failed} rows failed - use the download button to see why each row was rejected.";
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

        $record->name = mb_trim((string) ($this->data['name'] ?? $record->name));
        $record->email = filled($this->data['email'] ?? null)
            ? mb_trim((string) $this->data['email'])
            : $record->email;
        $record->phone = filled($this->data['phone'] ?? null)
            ? mb_trim((string) $this->data['phone'])
            : $record->phone;
        $record->joining_date = filled($this->data['joining_date'] ?? null)
            ? $this->data['joining_date']
            : $record->joining_date;
        $record->status = StaffStatus::tryFrom((string) ($this->data['status'] ?? ''))
            ?? ($record->status ?? StaffStatus::Active);
    }
}
