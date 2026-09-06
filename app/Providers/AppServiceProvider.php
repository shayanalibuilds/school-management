<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // This app has no App\Models\User - admins are the first-class
        // authenticatable, so container-level resolution (e.g. Filament
        // import/export records storing the acting user) points at Admin.
        $this->app->bind(Authenticatable::class, Admin::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blaze::optimize()->in(resource_path('views/components'));
    }
}
