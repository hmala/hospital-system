<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrShift;
use Illuminate\Http\Request;

class HrShiftController extends Controller
{
    public function index()
    {
        $shifts = HrShift::all();
        return view('hr.shifts.index', compact('shifts'));
    }

    public function create()
    {
        // Will implement later
    }

    public function store(Request $request)
    {
        // Will implement later
    }

    public function edit(HrShift $shift)
    {
        // Will implement later
    }

    public function update(Request $request, HrShift $shift)
    {
        // Will implement later
    }

    public function destroy(HrShift $shift)
    {
        // Will implement later
    }
}
