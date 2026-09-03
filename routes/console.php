<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:check-deadline')
    ->dailyAt('08:20')
    ->timezone(config('app.timezone', 'Asia/Karachi'));
