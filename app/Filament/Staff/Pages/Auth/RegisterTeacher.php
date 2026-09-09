<?php

declare(strict_types=1);

namespace App\Filament\Staff\Pages\Auth;

use App\Enums\StaffStatus;
use App\Models\Staff;
use App\Models\Teacher;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\Register;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Two-step teacher sign-up.
 *
 * Step one asks for the CNIC recorded on the school's teacher roster. The
 * account form only ever renders after that lookup succeeds, so an
 * unregistered visitor cannot even see the fields - and repeated guesses
 * hit a per-IP rate limit. The teacher's name is pulled from the roster and
 * shown read-only: the roster is the single source of truth for it.
 */
final class RegisterTeacher extends Register
{
    /**
     * The roster entry that passed the CNIC check, kept on the Livewire
     * component so every later request re-validates it server-side.
     */
    public ?string $verifiedTeacherId = null;

    public function form(Schema $schema): Schema
    {
        if ($this->verifiedTeacherId === null) {
            return $schema
                ->components([
                    $this->getCnicFormComponent(),
                ]);
        }

        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    public function verifyCnic(): void
    {
        try {
            $this->rateLimit(5, method: 'cnic-lookup');
        } catch (TooManyRequestsException $tooManyRequestsException) {
            $seconds = $tooManyRequestsException->secondsUntilAvailable;

            Notification::make()
                ->title('Too many attempts')
                ->body('Please try again in '.(is_int($seconds) ? $seconds : 60).' seconds.')
                ->danger()
                ->send();

            return;
        }

        $raw = $this->data['cnic'] ?? '';
        $cnic = is_string($raw) ? Str::trim($raw) : '';

        if (blank($cnic)) {
            $this->sendLookupFailure();

            return;
        }

        $teacher = Teacher::query()->where('cnic', $cnic)->first();

        // The same failure message covers both a wrong CNIC and a roster
        // entry that already registered, so nothing about the roster is
        // given away to a guesser.
        if ($teacher === null || $teacher->isRegistered()) {
            $this->sendLookupFailure();

            return;
        }

        $this->verifiedTeacherId = $teacher->id;
        $this->data['name'] = $teacher->name;
        $this->data['cnic'] = null;

        $this->forgetStepSchemas();
    }

    public function getTitle(): string
    {
        return 'Teacher registration';
    }

    public function getHeading(): string
    {
        return 'Set up your teacher account';
    }

    public function getFormContentComponent(): Form
    {
        // The vendor page pins the form submit handler to `register`; the
        // CNIC step needs its own handler, so the form submits whichever
        // step is currently on screen.
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->verifiedTeacherId === null ? 'verifyCnic' : 'register')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        // The name, CNIC and roster link never come from the request: they
        // are resolved again from the verified roster entry, so a crafted
        // submission cannot invent a teacher that never passed the CNIC
        // check.
        $teacher = $this->resolveVerifiedTeacher();

        if (! $teacher instanceof Teacher) {
            throw ValidationException::withMessages([
                'cnic' => 'Your CNIC could not be verified. Please start again.',
            ]);
        }

        $credentials = Arr::only($data, ['email', 'password']);

        return Staff::create([
            'email' => $credentials['email'] ?? null,
            'password' => $credentials['password'] ?? null,
            'name' => $teacher->name,
            'cnic' => $teacher->cnic,
            'teacher_id' => $teacher->id,
            'joining_date' => today(),
            'status' => StaffStatus::Active->value,
        ]);
    }

    protected function getNameFormComponent(): TextInput
    {
        return TextInput::make('name')
            ->label('Name')
            ->disabled()
            ->dehydrated(false)
            ->helperText('Your name comes from the school roster and cannot be changed here.');
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(Staff::class);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn (
                #[SensitiveParameter] $state
            ): string => Hash::make(is_string($state) ? $state : ''))
            ->same('passwordConfirmation')
            ->validationAttribute('password');
    }

    /**
     * @return list<Action>
     */
    protected function getFormActions(): array
    {
        if ($this->verifiedTeacherId === null) {
            return [
                Action::make('verifyCnicFormAction')
                    ->label('Verify CNIC')
                    ->submit('verifyCnic'),
            ];
        }

        return [
            $this->getRegisterFormAction()->label('Create account'),
        ];
    }

    private function getCnicFormComponent(): TextInput
    {
        return TextInput::make('cnic')
            ->label('CNIC')
            ->placeholder('35202-1234567-1')
            ->required()
            ->maxLength(15)
            ->autofocus()
            ->helperText('Enter the CNIC your school recorded on the teacher roster.');
    }

    private function resolveVerifiedTeacher(): ?Teacher
    {
        if ($this->verifiedTeacherId === null) {
            return null;
        }

        $teacher = Teacher::query()->find($this->verifiedTeacherId);

        if ($teacher === null || $teacher->isRegistered()) {
            $this->verifiedTeacherId = null;
            $this->forgetStepSchemas();

            return null;
        }

        return $teacher;
    }

    /**
     * Filament caches each schema across Livewire requests, so the cached
     * `form` and `content` schemas must be dropped whenever the page steps
     * between the CNIC gate and the account form.
     */
    private function forgetStepSchemas(): void
    {
        unset($this->cachedSchemas['form'], $this->cachedSchemas['content']);
    }

    private function sendLookupFailure(): void
    {
        Notification::make()
            ->title('CNIC not found')
            ->body("We could not find this CNIC in the school's teacher records. Please contact the school office.")
            ->danger()
            ->send();
    }
}
