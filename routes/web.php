<?php

declare(strict_types=1);

use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')
    ->name('home');

Route::post('/payments/callback/{provider}', PaymentCallbackController::class)
    ->name('payments.callback');

Route::get('/receipts/{payment}', ReceiptController::class)
    ->name('receipts.show');
