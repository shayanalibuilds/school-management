<?php

declare(strict_types=1);

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
