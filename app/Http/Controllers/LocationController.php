<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::orderBy('type')->orderBy('name')->get();
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
            'type' => 'required|in:main,sub',
        ]);

        Location::create($request->only(['name', 'type']));

        return redirect()->route('locations.index')->with('success', 'تم إضافة المخزن بنجاح');
    }

    public function show(Location $location)
    {
        $products = Product::with(['stockBatches' => function ($q) use ($location) {
            $q->where('location_id', $location->id);
        }])->orderBy('name')->get();

        return view('locations.show', compact('location', 'products'));
    }
}
