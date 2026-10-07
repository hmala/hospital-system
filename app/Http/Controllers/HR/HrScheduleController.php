<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrSchedule;
use Illuminate\Http\Request;

class HrScheduleController extends Controller
{
    public function index()
    {
        return view('hr.schedules.index');
    }

    // Methods for roster building will be implemented next
}
