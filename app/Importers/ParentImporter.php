<?php

declare(strict_types=1);

namespace App\Importers;

use App\Models\StudentParent;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

final class ParentImporter extends Importer
{
    protected static ?string $model = StudentParent::class;

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
            ImportColumn::make('occupation')
                ->rules(['nullable', 'max:100']),
            ImportColumn::make('children_gr_no')
                ->label('Children GR # (semicolon-separated)')
                ->rules(['nullable', 'max:500']),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Imported '.number_format($import->successful_rows).' parents.';
    }

    public function resolveRecord(): ?StudentParent
    {
        $cnic = mb_trim((string) ($this->data['cnic'] ?? ''));

        if ($cnic === '') {
            return null;
        }

        return StudentParent::query()->firstOrNew(['cnic' => $cnic]);
    }

    public function beforeSave(): void
    {
        $record = $this->getRecord();

        $record->name = mb_trim((string) ($this->data['name'] ?? $record->name));
        $record->phone = mb_trim((string) ($this->data['phone'] ?? $record->phone));
        $record->occupation = filled($this->data['occupation'] ?? null) ? (string) $this->data['occupation'] : $record->occupation;
    }

    public function afterSave(): void
    {
        $this->getRecord()
            ->students()
            ->syncWithoutDetaching(self::studentIdsByGrNoList($this->data['children_gr_no'] ?? null));
    }
}
