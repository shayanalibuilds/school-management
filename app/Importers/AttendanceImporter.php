<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class AttendanceImporter extends Importer
{
    protected static ?string $model = Attendance::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('student_gr_no')
                ->label('Student GR #')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('date')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'in:present,absent,leave']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.$import->successful_rows->format('0,0').' attendance rows.';
    }

    public function resolveRecord(): ?Attendance
    {
        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);

        if ($student === null) {
            return null;
        }

        return Attendance::query()
            ->where('student_id', $student->getKey())
            ->whereDate('date', (string) ($this->data['date'] ?? ''))
            ->first()
            ?? new Attendance([
                'student_id' => $student->getKey(),
                'date' => $this->data['date'],
            ]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();
        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);

        if ($student === null) {
            return;
        }

        $record->student_class_id = $student->student_class_id;
        $record->status = AttendanceStatus::from((string) ($this->data['status'] ?? 'present'));
    }
}
