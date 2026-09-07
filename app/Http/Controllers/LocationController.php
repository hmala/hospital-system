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

    public function edit(Location $location)
    {
        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
            'type' => 'required|in:main,sub',
        ]);

        $location->update($request->only(['name', 'type']));

        return redirect()->route('locations.index')->with('success', 'تم تحديث بيانات المخزن بنجاح');
    }

    public function destroy(Location $location)
    {
        // حماية المخزن الرئيسي الافتراضي
        if ($location->type === 'main' && Location::where('type', 'main')->count() <= 1) {
            return back()->with('error', 'لا يمكن حذف المخزن الرئيسي الوحيد في النظام.');
        }

        // التحقق من وجود رصيد مواد في المخزن
        $hasStock = $location->stockBatches()->where('current_qty', '>', 0)->exists();
        if ($hasStock) {
            return back()->with('error', 'لا يمكن حذف المخزن لوجود رصيد مواد متوفر بداخله. يرجى نقل المواد لمخزن آخر أولاً.');
        }

        // التحقق من وجود طلبات نقل معلقة
        $hasPendingRequests = \App\Models\StockTransferRequest::where('status', 'pending')
            ->where(function ($q) use ($location) {
                $q->where('from_location_id', $location->id)
                  ->orWhere('to_location_id', $location->id);
            })->exists();

        if ($hasPendingRequests) {
            return back()->with('error', 'لا يمكن حذف المخزن لوجود طلبات نقل معلقة مرتبطة به.');
        }

        $location->delete();

        return redirect()->route('locations.index')->with('success', 'تم حذف المخزن بنجاح');
    }
}
