<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\Staff;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class PayrollImporter extends Importer
{
    protected static ?string $model = Payroll::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('staff_cnic')
                ->label('Staff CNIC')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('month')
                ->requiredMapping()
                ->rules(['required', 'regex:/^\d{4}-\d{2}$/']),
            ImportColumn::make('amount')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:pending,paid']),
            ImportColumn::make('paid_at')
                ->rules(['nullable', 'date']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' payrolls.';
    }

    public function resolveRecord(): ?Payroll
    {
        $staff = Staff::query()->where('cnic', mb_trim((string) ($this->data['staff_cnic'] ?? '')))->first();

        if ($staff === null) {
            return null;
        }

        return Payroll::query()
            ->firstOrNew([
                'staff_id' => $staff->getKey(),
                'month' => mb_trim((string) ($this->data['month'] ?? '')),
            ]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->amount = (float) ($this->data['amount'] ?? 0);
        $record->status = PayrollStatus::tryFrom((string) ($this->data['status'] ?? '')) ?? PayrollStatus::Pending;
        $record->paid_at = filled($this->data['paid_at'] ?? null) ? $this->data['paid_at'] : $record->paid_at;
    }
}
