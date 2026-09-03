<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

final class CardController extends Controller
{
    public function student(Student $student): View
    {
        $student->load('studentClass');

        return view('cards.student', [
            'student' => $student,
        ]);
    }

    public function me(): View
    {
        $staff = Auth::guard('staff')->user();

        abort_if(! $staff instanceof Staff, 403);

        return view('cards.staff', [
            'staff' => $staff,
        ]);
    }
}
