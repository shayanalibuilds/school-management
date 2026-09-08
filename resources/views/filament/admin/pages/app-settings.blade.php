<x-filament-panels::page>
<div style="display: grid; gap: 1.5rem; max-width: 48rem;">
    <x-filament::section
        description="Toggle application-wide features."
    >
        <div style="display: grid; gap: 1.25rem;">
            <div class="ledger-card" style="display: flex; gap: 1rem; align-items: flex-start; justify-content: space-between; padding: 1rem;">
                <div>
                    <p style="font-weight: 600; margin: 0 0 0.25rem;" class="ledger-strong">Queue everything</p>
                    <p style="font-size: 0.8rem; margin: 0;" class="ledger-muted">
                        When enabled, every write &mdash; attendance and results saves, publishing &mdash;
                        is processed by the background queue instead of during the page request,
                        keeping the UI responsive under load. Reads always stay direct.
                        Requires a running queue worker; <code>composer dev</code> starts one automatically.
                    </p>
                </div>
                <x-filament::input.wrapper class="!w-auto shrink-0">
                    <x-filament::input.checkbox
                        wire:model.live="queueEverything"
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="ledger-card" style="display: flex; gap: 1rem; align-items: flex-start; justify-content: space-between; padding: 1rem;">
                <div>
                    <p style="font-weight: 600; margin: 0 0 0.25rem;" class="ledger-strong">Monthly stats</p>
                    <p style="font-size: 0.8rem; margin: 0;" class="ledger-muted">
                        When enabled, the admin dashboard shows school progress stats &mdash;
                        monthly income from completed fee payments, estimated spending
                        (paid salaries plus recurring expenses) and the net result, for the
                        last 12 months. Admin only: staff panels never show these stats.
                    </p>
                </div>
                <x-filament::input.wrapper class="!w-auto shrink-0">
                    <x-filament::input.checkbox
                        wire:model.live="statsEnabled"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>
</div>
</x-filament-panels::page>
