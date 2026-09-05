<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses;

use App\Filament\Resources\StudentClasses\Pages\CreateStudentClass;
use App\Filament\Resources\StudentClasses\Pages\EditStudentClass;
use App\Filament\Resources\StudentClasses\Pages\ListStudentClasses;
use App\Filament\Resources\StudentClasses\Schemas\StudentClassForm;
use App\Filament\Resources\StudentClasses\Tables\StudentClassesTable;
use App\Models\StudentClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class StudentClassResource extends Resource
{
    protected static ?string $model = StudentClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Classes';

    // Everywhere a user reads it, the model is just a "Class" — never
    // the dev-facing "Student class".
    protected static ?string $modelLabel = 'Class';

    protected static ?string $pluralModelLabel = 'Classes';

    public static function form(Schema $schema): Schema
    {
        return StudentClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentClassesTable::configure($table);
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
            'index' => ListStudentClasses::route('/'),
            'create' => CreateStudentClass::route('/create'),
            'edit' => EditStudentClass::route('/{record}/edit'),
        ];
    }
}
