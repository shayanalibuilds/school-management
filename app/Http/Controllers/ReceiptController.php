<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ReceiptController extends Controller
{
    public function __invoke(Request $request, Payment $payment): View
    {
        $payment->load(['fee.student.studentClass', 'fee.feeStructure']);

        return view('receipts.show', [
            'payment' => $payment,
        ]);
    }
}
