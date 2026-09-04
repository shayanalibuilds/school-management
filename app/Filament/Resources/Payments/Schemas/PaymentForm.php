<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Filament\Support\WizardSubmitActions;
use App\Models\Fee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Payment')
                        ->icon('heroicon-m-credit-card')
                        ->schema([
                            Select::make('fee_id')
                                ->label('Fee')
                                ->options(fn (): array => Fee::query()
                                    ->with(['student', 'feeStructure'])
                                    ->get()
                                    ->mapWithKeys(fn (Fee $fee): array => [
                                        $fee->getKey() => sprintf(
                                            '%s — %s (%s) — %s %d',
                                            $fee->student->name,
                                            $fee->feeStructure->name,
                                            $fee->student->studentClass?->name ?? '—',
                                            'Year',
                                            $fee->year,
                                        ),
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('provider')
                                ->options(collect(PaymentProvider::cases())
                                    ->mapWithKeys(fn (PaymentProvider $provider): array => [$provider->value => $provider->label()])
                                    ->all())
                                ->default(PaymentProvider::EasyPaisa->value)
                                ->required(),
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('PKR')
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Payer & reference')
                        ->icon('heroicon-m-identification')
                        ->schema([
                            TextInput::make('payer_name')
                                ->maxLength(150),
                            TextInput::make('payer_cnic')
                                ->maxLength(15),
                            TextInput::make('payer_phone')
                                ->maxLength(20),
                            Select::make('status')
                                ->options(collect(PaymentStatus::cases())
                                    ->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])
                                    ->all())
                                ->default(PaymentStatus::Completed->value)
                                ->required()
                                ->helperText('Record “Completed” for counter payments; online payments update automatically.'),
                            TextInput::make('reference')
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
