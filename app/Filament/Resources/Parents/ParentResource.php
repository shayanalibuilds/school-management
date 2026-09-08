<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents;

use App\Filament\Resources\Parents\Pages\CreateParent;
use App\Filament\Resources\Parents\Pages\EditParent;
use App\Filament\Resources\Parents\Pages\ListParents;
use App\Filament\Resources\Parents\Schemas\ParentForm;
use App\Filament\Resources\Parents\Tables\ParentsTable;
use App\Filament\Support\ArchiveNavigation;
use App\Models\StudentParent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ParentResource extends Resource
{
    protected static ?string $model = StudentParent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Parents';

    protected static ?string $modelLabel = 'Parent';

    public static function form(Schema $schema): Schema
    {
        return ParentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParents::route('/'),
            'create' => CreateParent::route('/create'),
            'edit' => EditParent::route('/{record}/edit'),
        ];
    }

    /**
     * The sidebar entry is an accordion: the active and archived views of
     * this ledger are nested entries underneath it.
     */
    public static function getNavigationItems(): array
    {
        return ArchiveNavigation::make(self::class);
    }
}
