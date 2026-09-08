<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;

use function Filament\Support\original_request;

/**
 * A sidebar accordion for an archivable resource: the resource entry becomes
 * a parent heading and its two views — the active ledger and the archived
 * one — become child entries nested underneath it. Nothing is ever deleted
 * in this system, so the archived side of every record deserves its own
 * visible door instead of a hidden tab.
 */
final class ArchiveNavigation
{
    /**
     * @param  class-string<resource>  $resource
     * @return array<NavigationItem>
     */
    public static function make(string $resource): array
    {
        $routeBaseName = $resource::getRouteBaseName();
        $plural = mb_strtolower($resource::getPluralModelLabel());

        return [
            NavigationItem::make($resource::getNavigationLabel())
                ->key($resource)
                ->group($resource::getNavigationGroup())
                ->icon($resource::getNavigationIcon())
                ->sort($resource::getNavigationSort())
                ->childItems([
                    NavigationItem::make("Active {$plural}")
                        ->sort(1)
                        ->url(fn (): string => $resource::getUrl('index'))
                        ->isActiveWhen(fn (): bool => original_request()->routeIs($routeBaseName.'.index')
                            && original_request()->query('tab') !== 'inactive'),
                    NavigationItem::make("Inactive {$plural}")
                        ->sort(2)
                        ->url(fn (): string => $resource::getUrl('index').'?tab=inactive')
                        ->isActiveWhen(fn (): bool => original_request()->routeIs($routeBaseName.'.index')
                            && original_request()->query('tab') === 'inactive'),
                ]),
        ];
    }
}
