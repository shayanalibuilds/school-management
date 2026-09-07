<?php

declare(strict_types=1);

use App\Importers\StudentImporter;

function countedNoun(int $count, string $singular, ?string $plural = null): string
{
    $method = new ReflectionMethod(StudentImporter::class, 'countedNoun');
    $method->setAccessible(true);

    return $method->invoke(null, $count, $singular, $plural);
}

it('inflects importer notification nouns for singular and plural counts', function (): void {
    expect(countedNoun(1, 'student'))->toBe('1 student')
        ->and(countedNoun(2, 'student'))->toBe('2 students')
        ->and(countedNoun(1200, 'student'))->toBe('1,200 students')
        ->and(countedNoun(1, 'row'))->toBe('1 row')
        ->and(countedNoun(3, 'row'))->toBe('3 rows')
        ->and(countedNoun(1, 'fee', 'fees'))->toBe('1 fee')
        ->and(countedNoun(5, 'fee', 'fees'))->toBe('5 fees')
        ->and(countedNoun(1, 'exam result'))->toBe('1 exam result')
        ->and(countedNoun(9, 'exam result'))->toBe('9 exam results');
});
