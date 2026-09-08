<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests;

use App\Filament\Staff\Resources\MyRequests\Pages\CreateMyRequest;
use App\Filament\Staff\Resources\MyRequests\Pages\ListMyRequests;
use App\Filament\Staff\Resources\MyRequests\Schemas\MyRequestForm;
use App\Filament\Staff\Resources\MyRequests\Tables\MyRequestsTable;
use App\Models\StaffAssignmentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class MyRequestResource extends Resource
{
    protected static ?string $model = StaffAssignmentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Teaching';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'My Requests';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return MyRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MyRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyRequests::route('/'),
            'create' => CreateMyRequest::route('/create'),
        ];
    }
}
