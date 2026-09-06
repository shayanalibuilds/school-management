<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\StudentStatus;
use App\Models\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('gr_no')
                ->label('GR #')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('class')
                ->rules(['max:100']),
            ImportColumn::make('joining_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:active,graduated,left']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.$import->successful_rows->format('0,0').' students.';
    }

    public function resolveRecord(): ?Student
    {
        $grNo = mb_trim((string) ($this->data['gr_no'] ?? ''));

        if ($grNo === '') {
            return null;
        }

        return Student::query()->firstOrNew(['gr_no' => $grNo]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->name = mb_trim((string) ($this->data['name'] ?? $record->name));
        $record->student_class_id = self::classByName($this->data['class'] ?? null)?->getKey()
            ?? $record->student_class_id;
        $record->joining_date = filled($this->data['joining_date'] ?? null)
            ? $this->data['joining_date']
            : $record->joining_date;
        $record->status = StudentStatus::tryFrom((string) ($this->data['status'] ?? ''))
            ?? ($record->status ?? StudentStatus::Active);
    }
}
