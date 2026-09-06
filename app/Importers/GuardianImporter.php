<?php

declare(strict_types=1);

namespace App\Importers;

use App\Models\Guardian;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class GuardianImporter extends Importer
{
    protected static ?string $model = Guardian::class;

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
                ->requiredMapping()
                ->rules(['required', 'max:20']),
            ImportColumn::make('phone')
                ->requiredMapping()
                ->rules(['required', 'max:30']),
            ImportColumn::make('relation')
                ->rules(['nullable', 'max:50']),
            ImportColumn::make('students_gr_no')
                ->label('Students GR # (semicolon-separated)')
                ->rules(['nullable', 'max:500']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.$import->successful_rows->format('0,0').' guardians.';
    }

    public function resolveRecord(): ?Guardian
    {
        $cnic = mb_trim((string) ($this->data['cnic'] ?? ''));

        if ($cnic === '') {
            return null;
        }

        return Guardian::query()->firstOrNew(['cnic' => $cnic]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->name = mb_trim((string) ($this->data['name'] ?? $record->name));
        $record->phone = mb_trim((string) ($this->data['phone'] ?? $record->phone));
        $record->relation = filled($this->data['relation'] ?? null) ? (string) $this->data['relation'] : $record->relation;
    }

    public function afterSave(): void
    {
        $this->getRecord()
            ->students()
            ->syncWithoutDetaching(self::studentIdsByGrNoList($this->data['students_gr_no'] ?? null));
    }
}
