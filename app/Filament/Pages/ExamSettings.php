<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\GradingScale;
use App\Models\MarkingScheme;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\AppSettings as AppSettingsStore;
use App\Support\Grades;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

final class ExamSettings extends Page
{
    /**
     * Mark limits per subject of the selected class, keyed by subject id.
     *
     * @var array<string, array<string, string>>
     */
    public array $schemes = [];

    /**
     * Editable grading scale rows. Rows with an id update existing
     * grades, rows without are created on save, missing ids are deleted.
     *
     * @var list<array{id: ?string, name: string, min_percentage: string}>
     */
    public array $scale = [];

    public string $reportMode = 'grades';

    public ?string $schemeClassId = null;

    protected string $view = 'filament.admin.pages.exam-settings';

    protected ?string $heading = 'Exam settings';

    protected static ?string $navigationLabel = 'Exam settings';

    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    public function mount(): void
    {
        $this->reportMode = AppSettingsStore::examReportMode();
        $this->schemeClassId = $this->classes->first()?->getKey();
        $this->loadSchemes();
        $this->loadScale();
    }

    /**
     * Classes that can receive a marking scheme.
     *
     * @return Collection<int, StudentClass>
     */
    public function getClassesProperty(): Collection
    {
        return StudentClass::query()->where('status', 'active')->orderBy('name')->get();
    }

    /**
     * Subjects of the selected class — only combinations that actually
     * exist on the class timetable can carry a marking scheme.
     *
     * @return Collection<int, Subject>
     */
    public function getSchemeSubjectsProperty(): Collection
    {
        $class = $this->schemeClass;

        if ($class === null) {
            /** @var Collection<int, Subject> */
            return collect();
        }

        return $class->subjects()
            ->where('subjects.status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return StudentClass|null
     */
    public function getSchemeClassProperty(): ?StudentClass
    {
        if ($this->schemeClassId === null) {
            return null;
        }

        return StudentClass::query()->find($this->schemeClassId);
    }

    public function updatedSchemeClassId(): void
    {
        $this->loadSchemes();
    }

    /**
     * Prefill the mark-limit inputs from the stored schemes, defaulting
     * to the legacy 0 - 100 scale for subjects without one yet.
     */
    public function loadSchemes(): void
    {
        $this->schemes = [];

        foreach ($this->schemeSubjects as $subject) {
            $bounds = MarkingScheme::boundsFor((string) $this->schemeClassId, $subject->getKey());

            $this->schemes[$subject->getKey()] = [
                'min' => self::formatMarks($bounds['min']),
                'max' => self::formatMarks($bounds['max']),
            ];
        }
    }

    public function loadScale(): void
    {
        $this->scale = GradingScale::query()
            ->orderByDesc('min_percentage')
            ->orderBy('name')
            ->get()
            ->map(fn (GradingScale $grade): array => [
                'id' => $grade->getKey(),
                'name' => $grade->name,
                'min_percentage' => self::formatMarks($grade->min_percentage),
            ])
            ->all();
    }

    /**
     * Render a mark threshold without trailing zeros: 0.0 -> "0",
     * 100.0 -> "100", 7.5 -> "7.5".
     */
    private static function formatMarks(float $value): string
    {
        return (string) $value;
    }

    public function addScaleRow(): void
    {
        $this->scale[] = ['id' => null, 'name' => '', 'min_percentage' => ''];
    }

    public function removeScaleRow(int $index): void
    {
        unset($this->scale[$index]);

        $this->scale = array_values($this->scale);
    }

    public function saveReportMode(): void
    {
        if (! in_array($this->reportMode, ['grades', 'positions'], true)) {
            $this->reportMode = 'grades';
        }

        AppSettingsStore::set(AppSettingsStore::EXAM_REPORT_MODE, $this->reportMode);

        FilamentNotification::make()
            ->title($this->reportMode === 'grades' ? 'Results are reported as grades' : 'Results are reported as positions')
            ->body($this->reportMode === 'grades'
                ? 'The public gazette shows letter grades from the grading scale.'
                : 'The public gazette shows class positions from total marks.')
            ->success()
            ->send();
    }

    public function saveSchemes(): void
    {
        $class = $this->schemeClass;

        if ($class === null) {
            FilamentNotification::make()
                ->title('Select a class first')
                ->danger()
                ->send();

            return;
        }

        $prepared = [];

        foreach ($this->schemeSubjects as $subject) {
            $state = $this->schemes[$subject->getKey()] ?? ['min' => '0', 'max' => '100'];

            $min = $state['min'] === '' ? 0.0 : (float) $state['min'];
            $max = $state['max'] === '' ? 100.0 : (float) $state['max'];

            if (! is_numeric($state['min'] === '' ? '0' : $state['min']) || ! is_numeric($state['max'] === '' ? '100' : $state['max'])
                || $min < 0 || $max <= 0 || $min > $max) {
                FilamentNotification::make()
                    ->title('Invalid marks for '.$subject->name)
                    ->body('Minimum must be 0 or more, maximum must be greater than 0, and minimum cannot exceed maximum.')
                    ->danger()
                    ->send();

                return;
            }

            $prepared[$subject->getKey()] = ['min' => $min, 'max' => $max];
        }

        foreach ($prepared as $subjectId => $bounds) {
            MarkingScheme::query()->updateOrCreate(
                [
                    'student_class_id' => $class->getKey(),
                    'subject_id' => $subjectId,
                ],
                [
                    'min_marks' => $bounds['min'],
                    'max_marks' => $bounds['max'],
                ],
            );
        }

        $this->loadSchemes();

        FilamentNotification::make()
            ->title('Marking scheme saved')
            ->body('Teachers can now only enter marks between the configured minimum and maximum for '.$class->name.'.')
            ->success()
            ->send();
    }

    public function saveScale(): void
    {
        // Validate everything first so a rejected save never leaves the
        // scale half-written, then apply the whole plan in one pass.
        $plan = [];
        $seen = [];

        foreach ($this->scale as $row) {
            $name = mb_trim($row['name']);
            $min = mb_trim($row['min_percentage']);

            if ($name === '' && $min === '') {
                FilamentNotification::make()
                    ->title('Empty grade row')
                    ->body('Enter a grade name and threshold, or remove the empty row.')
                    ->danger()
                    ->send();

                return;
            }

            if ($name === '') {
                FilamentNotification::make()
                    ->title('Grade name missing')
                    ->body('Every row needs a grade name, for example A+ or B.')
                    ->danger()
                    ->send();

                return;
            }

            if (! is_numeric($min) || (float) $min < 0 || (float) $min > 100) {
                FilamentNotification::make()
                    ->title('Invalid threshold for '.$name)
                    ->body('The threshold must be a percentage between 0 and 100.')
                    ->danger()
                    ->send();

                return;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                FilamentNotification::make()
                    ->title('Duplicate grade name')
                    ->body('"'.$name.'" is used more than once — each grade name can only appear once.')
                    ->danger()
                    ->send();

                return;
            }

            $seen[$key] = true;

            $plan[] = ['id' => $row['id'], 'name' => $name, 'min' => (float) $min];
        }

        $keptIds = [];

        foreach ($plan as $row) {
            if ($row['id'] !== null) {
                GradingScale::query()->whereKey($row['id'])->update([
                    'name' => $row['name'],
                    'min_percentage' => $row['min'],
                ]);
            } else {
                $created = GradingScale::query()->create([
                    'name' => $row['name'],
                    'min_percentage' => $row['min'],
                ]);

                $row['id'] = $created->getKey();
            }

            $keptIds[] = $row['id'];
        }

        GradingScale::query()->whereNotIn('id', $keptIds)->delete();

        Grades::flushCache();
        $this->loadScale();

        FilamentNotification::make()
            ->title('Grading scale saved')
            ->body(count($this->scale) === 0
                ? 'The scale is empty, so the built-in boundaries apply (A+ 90, A 80, B 70, C 60, D 50).'
                : 'Marks are now graded against your thresholds.')
            ->success()
            ->send();
    }
}
