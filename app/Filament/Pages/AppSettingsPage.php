<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Support\AppSettings as AppSettingsStore;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use UnitEnum;

final class AppSettingsPage extends Page
{
    public bool $queueEverything = false;

    protected string $view = 'filament.admin.pages.app-settings';

    protected static ?string $navigationLabel = 'App settings';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public function mount(): void
    {
        $this->queueEverything = AppSettingsStore::queueEverything();
    }

    public function updatedQueueEverything(bool $value): void
    {
        AppSettingsStore::set(AppSettingsStore::QUEUE_EVERYTHING, $value ? '1' : '0');

        FilamentNotification::make()
            ->title($value ? 'Queue everything enabled' : 'Queue everything disabled')
            ->body($value
                ? 'Writes are now processed by the background queue.'
                : 'Writes now run directly during the page request.')
            ->success()
            ->send();
    }
}
