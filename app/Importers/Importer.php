<?php

declare(strict_types=1);

namespace App\Importers;

use App\Models\Student;
use App\Models\StudentClass;
use Filament\Actions\Imports\Importer as FilamentImporter;
use Illuminate\Support\Str;

abstract class Importer extends FilamentImporter
{
    /**
     * Every importer sets its attributes explicitly in beforeSave(),
     * because several CSV columns (class, GR #s, subject names, ...)
     * are virtual lookups rather than model attributes - the automatic
     * per-column fill would push them onto the record and break the
     * SQL statement.
     */
    #[Override]
    final public function fillRecord(): void {}

    /**
     * A formatted count with the correctly inflected noun, so
     * notifications read "1 student" and "12 students" alike.
     */
    protected static function countedNoun(int $count, string $singular, ?string $plural = null): string
    {
        $noun = $count === 1 ? $singular : ($plural ?? Str::plural($singular));

        return number_format($count).' '.$noun;
    }

    /**
     * Look up a student by their one and only identifier: the GR #.
     */
    protected static function studentByGrNo(mixed $value): ?Student
    {
        $grNo = mb_trim((string) ($value ?? ''));

        if ($grNo === '') {
            return null;
        }

        return Student::query()->where('gr_no', $grNo)->first();
    }

    /**
     * Resolve a class by its name. Schools import students whose class
     * names come from their own records, so a class that does not exist
     * yet is created on the fly instead of failing the whole import.
     */
    protected static function classByName(mixed $value): ?StudentClass
    {
        $name = mb_trim((string) ($value ?? ''));

        if ($name === '') {
            return null;
        }

        return StudentClass::query()->firstOrCreate(['name' => $name]);
    }

    /**
     * Turn a semicolon-separated GR # list into student ids.
     *
     * @return list<string>
     */
    protected static function studentIdsByGrNoList(mixed $value): array
    {
        $grNos = collect(explode(';', (string) ($value ?? '')))
            ->map(fn (string $grNo): string => mb_trim($grNo))
            ->filter(fn (string $grNo): bool => $grNo !== '')
            ->all();

        if ($grNos === []) {
            return [];
        }

        return Student::query()
            ->whereIn('gr_no', $grNos)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
    }
}
