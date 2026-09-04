<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * Shared helper for table bulk edit actions: applies only the fields the
 * admin actually filled in, so untouched inputs never overwrite records.
 */
final class BulkEdit
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  iterable<int, TModel>  $records
     * @param  array<array-key, mixed>  $data
     */
    public static function apply(iterable $records, array $data): void
    {
        /** @var array<string, mixed> $payload */
        $payload = collect($data)
            ->filter(fn (mixed $value): bool => filled($value))
            ->all();

        foreach ($records as $record) {
            $record->update($payload);
        }
    }
}
