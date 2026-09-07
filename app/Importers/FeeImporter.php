<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\FeeStructure;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class FeeImporter extends Importer
{
    protected static ?string $model = Fee::class;

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
            ImportColumn::make('fee')
                ->label('Fee name')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('year')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'integer', 'min:2000', 'max:2100']),
            ImportColumn::make('amount')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('amount_paid')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:unpaid,partial,paid']),
            ImportColumn::make('due_date')
                ->rules(['nullable', 'date']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return self::countedNoun((int) $import->successful_rows, 'fee', 'fees').' imported.';
    }

    public function resolveRecord(): ?Fee
    {
        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);
        $feeStructure = FeeStructure::query()->where('name', mb_trim((string) ($this->data['fee'] ?? '')))->first();

        if (! $student instanceof \App\Models\Student || $feeStructure === null) {
            return null;
        }

        return Fee::query()
            ->firstOrNew([
                'student_id' => $student->getKey(),
                'fee_structure_id' => $feeStructure->getKey(),
                'year' => (int) ($this->data['year'] ?? today()->year),
            ]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->amount = (float) ($this->data['amount'] ?? 0);
        $record->amount_paid = (float) ($this->data['amount_paid'] ?? 0);
        $record->status = FeeStatus::tryFrom((string) ($this->data['status'] ?? '')) ?? FeeStatus::Unpaid;
        $record->due_date = filled($this->data['due_date'] ?? null) ? $this->data['due_date'] : $record->due_date;
    }
}
