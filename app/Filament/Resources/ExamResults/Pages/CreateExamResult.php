<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Pages;

use App\Filament\Resources\ExamResults\ExamResultResource;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateExamResult extends CreateRecord
{
    protected static string $resource = ExamResultResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['student_class_id'] = Student::query()->findOrFail($data['student_id'])->student_class_id;

        return self::getModel()::create($data);
    }
}
