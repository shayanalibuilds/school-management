<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\ExamResultStatus;
use App\Models\ExamResult;
use App\Models\Subject;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class ExamResultImporter extends Importer
{
    protected static ?string $model = ExamResult::class;

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
            ImportColumn::make('subject')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('year')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'integer', 'min:2000', 'max:2100']),
            ImportColumn::make('marks')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0', 'max:100']),
            ImportColumn::make('total_marks')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:1']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return self::countedNoun((int) $import->successful_rows, 'exam result').' imported.';
    }

    /**
     * Records are updated in place. Locked sheets (published more than
     * 30 days ago) refuse imported corrections, and newly imported rows
     * always start as drafts until someone publishes them.
     */
    public function resolveRecord(): ?ExamResult
    {
        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);
        $subject = Subject::query()->where('name', mb_trim((string) ($this->data['subject'] ?? '')))->first();

        if (! $student instanceof \App\Models\Student || $subject === null) {
            return null;
        }

        $year = (int) ($this->data['year'] ?? today()->year);

        if (ExamResult::sheetIsLocked((string) $student->student_class_id, (string) $subject->getKey(), $year)) {
            return null;
        }

        return ExamResult::query()
            ->firstOrNew([
                'student_id' => $student->getKey(),
                'subject_id' => $subject->getKey(),
                'year' => $year,
            ]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();
        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);
        $subject = Subject::query()->where('name', mb_trim((string) ($this->data['subject'] ?? '')))->first();

        if (! $student instanceof \App\Models\Student || $subject === null) {
            return;
        }

        $record->student_class_id = $student->student_class_id;
        $record->marks = (float) ($this->data['marks'] ?? 0);
        $record->total_marks = (float) ($this->data['total_marks'] ?? 100);

        if (! $record->exists) {
            $record->status = ExamResultStatus::Draft;
        }
    }
}
