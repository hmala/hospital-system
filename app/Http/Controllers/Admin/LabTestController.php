<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function index()
    {
        $tests = LabTest::with('subTests')
            ->orderBy('main_category')
            ->orderBy('subcategory')
            ->orderBy('name')
            ->paginate(50);
        
        return view('admin.lab-tests.index', compact('tests'));
    }
}
