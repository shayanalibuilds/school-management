<div class="py-8 space-y-6">
    <div>
        <flux:heading size="lg">Payment Settings</flux:heading>
        <flux:subheading>
            Configure JazzCash and EasyPaisa credentials. Only active providers with complete
            credentials are offered to parents — credentials are stored encrypted.
        </flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        @foreach (\App\Enums\PaymentProvider::cases() as $provider)
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">{{ $provider->label() }}</flux:heading>
                    <flux:switch
                        wire:model="settings.{{ $provider->value }}.is_active"
                        label="Active"
                    />
                </div>

                <div>
                    <flux:label>Environment</flux:label>
                    <flux:select wire:model="settings.{{ $provider->value }}.environment">
                        <flux:select.option value="sandbox">Sandbox</flux:select.option>
                        <flux:select.option value="live">Live</flux:select.option>
                    </flux:select>
                </div>

                @foreach ($provider->requiredCredentials() as $key)
                    <div>
                        <flux:label>{{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->title() }}</flux:label>
                        <flux:input
                            wire:model="settings.{{ $provider->value }}.{{ $key }}"
                            type="text"
                        />
                    </div>
                @endforeach
            </flux:card>
        @endforeach

        <div>
            <flux:button variant="primary" type="submit" icon="check">Save settings</flux:button>
        </div>
    </form>
</div>
