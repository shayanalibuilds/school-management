<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AppSettings
{
    public const string QUEUE_EVERYTHING = 'queue_everything';

    public const string STATS_ENABLED = 'stats_enabled';

    public static function get(string $key, ?string $default = null): ?string
    {
        $cached = Cache::remember("app_settings:{$key}", now()->addMinutes(5), function () use ($key, $default): ?string {
            try {
                $row = DB::table('app_settings')->where('key', $key)->value('value');
            } catch (RuntimeException|\Illuminate\Database\QueryException) {
                return $default;
            }

            return is_scalar($row) ? (string) $row : $default;
        });

        return $cached === null ? null : (string) $cached;
    }

    public static function set(string $key, string $value): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget("app_settings:{$key}");
    }

    /**
     * When enabled, write operations (attendance and results saves,
     * publishing) are pushed onto the queue instead of running inside
     * the web request, keeping the UI responsive under load. Read
     * operations always run directly.
     */
    public static function queueEverything(): bool
    {
        return self::get(self::QUEUE_EVERYTHING, '0') === '1';
    }

    /**
     * When enabled, the admin dashboard shows the school progress
     * widgets: monthly income from completed fee payments, estimated
     * spending (paid salaries plus recurring expenses) and the net
     * result, so the admin can follow the school's month-by-month
     * progress. Admin-only — staff panels never render these stats.
     */
    public static function statsEnabled(): bool
    {
        return self::get(self::STATS_ENABLED, '0') === '1';
    }
}
