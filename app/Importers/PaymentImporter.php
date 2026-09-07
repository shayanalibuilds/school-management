<?php

declare(strict_types=1);

namespace App\Importers;

use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Payment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class PaymentImporter extends Importer
{
    protected static ?string $model = Payment::class;

    /**
     * @return list<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('reference')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('fee')
                ->label('Fee name')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('student_gr_no')
                ->label('Student GR #')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('amount')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('provider')
                ->rules(['nullable', 'in:jazzcash,easypaisa']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:pending,completed,failed']),
            ImportColumn::make('payer_name')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('paid_at')
                ->rules(['nullable', 'date']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' payments.';
    }

    public function resolveRecord(): ?Payment
    {
        $reference = mb_trim((string) ($this->data['reference'] ?? ''));

        if ($reference === '') {
            return null;
        }

        return Payment::query()->firstOrNew(['reference' => $reference]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $student = self::studentByGrNo($this->data['student_gr_no'] ?? null);
        $fee = $student instanceof \App\Models\Student
            ? Fee::query()
                ->where('student_id', $student->getKey())
                ->whereHas('feeStructure', fn ($query) => $query->where('name', mb_trim((string) ($this->data['fee'] ?? ''))))
                ->first()
            : null;

        if ($fee !== null) {
            $record->fee_id = $fee->getKey();
        }

        $record->amount = (float) ($this->data['amount'] ?? 0);
        $record->provider = \App\Enums\PaymentProvider::tryFrom((string) ($this->data['provider'] ?? '')) ?? $record->provider ?? \App\Enums\PaymentProvider::JazzCash;
        $record->status = PaymentStatus::tryFrom((string) ($this->data['status'] ?? '')) ?? PaymentStatus::Completed;
        $record->payer_name = filled($this->data['payer_name'] ?? null) ? (string) $this->data['payer_name'] : $record->payer_name;
        $record->paid_at = filled($this->data['paid_at'] ?? null) ? $this->data['paid_at'] : $record->paid_at;
    }
}
