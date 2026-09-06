<?php

declare(strict_types=1);

use App\Http\Controllers\CardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')
    ->name('home');

Route::livewire('/results', 'pages::results')
    ->name('results');

Route::livewire('/attendance', 'pages::attendance')
    ->name('attendance');

Route::livewire('/fees', 'pages::fees')
    ->name('fees');

Route::post('/payments/callback/{provider}', PaymentCallbackController::class)
    ->name('payments.callback');

Route::get('/receipts/{payment}', ReceiptController::class)
    ->name('receipts.show');

// Laravel's auth middleware redirects guests to route('login'); point it at
// the admin panel login so unauthenticated visits land somewhere sensible.
Route::redirect('/login', '/dashboard/login')
    ->name('login');

Route::middleware('auth:admin')->group(function (): void {
    Route::get('/cards/student/{student}', [CardController::class, 'student'])
        ->name('cards.student');

    Route::get('/exports/{type}', ExportController::class)
        ->where('type', 'students|staff|attendance|exam-results|fees|payments|payrolls|expenses|parents|guardians')
        ->name('exports');

    Route::post('/imports/students', [ImportController::class, 'students'])
        ->name('imports.students');

    Route::post('/imports/staff', [ImportController::class, 'staff'])
        ->name('imports.staff');

    Route::post('/imports/parents', [ImportController::class, 'parents'])
        ->name('imports.parents');

    Route::post('/imports/guardians', [ImportController::class, 'guardians'])
        ->name('imports.guardians');
});

Route::middleware('auth:staff')->group(function (): void {
    Route::get('/cards/me', [CardController::class, 'me'])
        ->name('cards.me');
});
