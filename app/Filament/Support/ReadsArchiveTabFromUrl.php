<?php

declare(strict_types=1);

namespace App\Filament\Support;

/**
 * Lets the sidebar's "Inactive …" accordion entries deep-link into the
 * archived view of a list page: the `?tab=` query parameter selects the
 * tab on load, exactly as if the user had clicked it.
 */
trait ReadsArchiveTabFromUrl
{
    protected function loadDefaultActiveTab(): void
    {
        $tab = request()->query('tab');

        if (is_string($tab) && array_key_exists($tab, $this->getCachedTabs())) {
            $this->activeTab = $tab;
        }

        parent::loadDefaultActiveTab();
    }
}
