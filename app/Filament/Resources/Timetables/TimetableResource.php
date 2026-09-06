<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables;

use App\Filament\Resources\Timetables\Pages\CreateTimetableSlot;
use App\Filament\Resources\Timetables\Pages\EditTimetableSlot;
use App\Filament\Resources\Timetables\Pages\ListTimetableSlots;
use App\Filament\Resources\Timetables\Schemas\TimetableForm;
use App\Filament\Resources\Timetables\Tables\TimetablesTable;
use App\Models\TimetableSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TimetableResource extends Resource
{
    protected static ?string $model = TimetableSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Timetable';

    protected static ?string $modelLabel = 'timetable slot';

    public static function form(Schema $schema): Schema
    {
        return TimetableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimetablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimetableSlots::route('/'),
            'create' => CreateTimetableSlot::route('/create'),
            'edit' => EditTimetableSlot::route('/{record}/edit'),
        ];
    }
}
