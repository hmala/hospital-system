<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->paginate(25);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:products,code',
            'unit' => 'required|string|max:100',
            'is_perishable' => 'required|boolean',
            'alert_quantity' => 'required|integer|min:0',
        ]);

        $code = $request->input('code');
        if (!$code) {
            $lastId = Product::max('id') ?: 0;
            $code = 'PRD-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

            // ضمان عدم التكرار في حال إدخال يدوي متسارع
            while (Product::where('code', $code)->exists()) {
                $lastId++;
                $code = 'PRD-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        }

        Product::create([
            'name' => $request->name,
            'code' => $code,
            'unit' => $request->unit,
            'is_perishable' => $request->is_perishable,
            'alert_quantity' => $request->alert_quantity,
        ]);

        return redirect()->route('products.index')->with('success', 'تم إضافة المادة بنجاح');
    }
}