<div style="display: grid; gap: 1.5rem;">
    <x-filament::section
        heading="Payment settings"
        description="Configure JazzCash and EasyPaisa credentials. Only active providers with complete credentials are offered to parents — credentials are stored encrypted."
    >
        <form wire:submit="save" style="display: grid; gap: 1.5rem;">
            @foreach (\App\Enums\PaymentProvider::cases() as $provider)
                <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; display: grid; gap: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                        <h2 style="margin: 0; font-size: 1rem; font-weight: 600; color: #111827;">
                            {{ $provider->label() }}
                        </h2>
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #374151;">
                            <x-filament::input.checkbox wire:model="settings.{{ $provider->value }}.is_active" />
                            Active
                        </label>
                    </div>

                    <x-filament-forms::field-wrapper
                        label="Environment"
                        :id="'settings-'.$provider->value.'-environment'"
                        :statePath="'settings.'.$provider->value.'.environment'"
                    >
                        <x-filament::input.select
                            :id="'settings-'.$provider->value.'-environment'"
                            wire:model="settings.{{ $provider->value }}.environment"
                        >
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </x-filament::input.select>
                    </x-filament-forms::field-wrapper>

                    @foreach ($provider->requiredCredentials() as $key)
                        <x-filament-forms::field-wrapper
                            :label="\Illuminate\Support\Str::of($key)->replace('_', ' ')->title()"
                            :id="'settings-'.$provider->value.'-'.$key"
                            :statePath="'settings.'.$provider->value.'.'.$key"
                        >
                            <x-filament::input.wrapper>
                                <x-filament::input
                                    :id="'settings-'.$provider->value.'-'.$key"
                                    type="text"
                                    wire:model="settings.{{ $provider->value }}.{{ $key }}"
                                />
                            </x-filament::input.wrapper>
                        </x-filament-forms::field-wrapper>
                    @endforeach
                </div>
            @endforeach

            <div>
                <x-filament::button type="submit" icon="heroicon-m-check">
                    Save settings
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</div>
