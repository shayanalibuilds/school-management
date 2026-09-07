<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\ExpenseRecurrence;
use App\Models\Expense;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class ExpenseImporter extends Importer
{
    protected static ?string $model = Expense::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:150']),
            ImportColumn::make('description')
                ->rules(['nullable', 'max:500']),
            ImportColumn::make('amount')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('recurrence')
                ->rules(['nullable', 'in:one_time,weekly,monthly,yearly']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return self::countedNoun((int) $import->successful_rows, 'expense').' imported.';
    }

    public function resolveRecord(): ?Expense
    {
        $name = mb_trim((string) ($this->data['name'] ?? ''));

        if ($name === '') {
            return null;
        }

        return Expense::query()->firstOrNew(['name' => $name]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->description = filled($this->data['description'] ?? null) ? (string) $this->data['description'] : $record->description;
        $record->amount = (float) ($this->data['amount'] ?? 0);
        $record->recurrence = ExpenseRecurrence::tryFrom((string) ($this->data['recurrence'] ?? '')) ?? $record->recurrence ?? ExpenseRecurrence::OneTime;
    }
}
