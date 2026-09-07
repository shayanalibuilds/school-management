<?php

declare(strict_types=1);

namespace App\Support;

final class GrNumber
{
    /**
     * Strip a leading "GR" prefix so the registry chip can re-apply
     * its own canonical formatting without doubling it (e.g. the seed
     * data stores "GR194746" but a CSV import may store "194746").
     */
    public static function bare(?string $grNo): string
    {
        return (string) preg_replace('/^\s*GR\s*/i', '', (string) $grNo);
    }
}
