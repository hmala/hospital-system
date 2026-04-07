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
        $categories = Product::distinct()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->orderBy('category')
            ->pluck('category');

        return view('products.create', compact('categories'));
    }

    public function edit(Product $product)
    {
        $categories = Product::distinct()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->orderBy('category')
            ->pluck('category');

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_custom' => 'required_if:category,__other__|nullable|string|max:255',
            'unit' => 'required|string|max:100',
            'is_perishable' => 'required|boolean',
            'alert_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'reorder_level' => 'nullable|integer|min:0',
            'storage_conditions' => 'nullable|string|max:255',
        ]);

        $category = $request->category;
        if ($category === '__other__') {
            $category = $request->category_custom;
        }

        $product->update([
            'name' => $request->name,
            'category' => $category,
            'unit' => $request->unit,
            'is_perishable' => $request->is_perishable,
            'alert_quantity' => $request->alert_quantity,
            'description' => $request->description,
            'reorder_level' => $request->reorder_level ?: 0,
            'storage_conditions' => $request->storage_conditions,
        ]);

        return redirect()->route('products.index')->with('success', 'تم تعديل المادة بنجاح');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'تم حذف المادة بنجاح');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_custom' => 'required_if:category,__other__|nullable|string|max:255',
            'unit' => 'required|string|max:100',
            'is_perishable' => 'required|boolean',
            'alert_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'reorder_level' => 'nullable|integer|min:0',
            'storage_conditions' => 'nullable|string|max:255',
        ]);

        $code = $request->input('code');
        if (!$code) {
            $lastId = Product::max('id') ?: 0;
            $code = 'PRD-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

            while (Product::where('code', $code)->exists()) {
                $lastId++;
                $code = 'PRD-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        }

        $category = $request->category;
        if ($category === '__other__') {
            $category = $request->category_custom;
        }

        Product::create([
            'name' => $request->name,
            'code' => $code,
            'category' => $category,
            'unit' => $request->unit,
            'is_perishable' => $request->is_perishable,
            'alert_quantity' => $request->alert_quantity,
            'description' => $request->description,
            'reorder_level' => $request->reorder_level ?: 0,
            'storage_conditions' => $request->storage_conditions,
        ]);

        return redirect()->route('products.index')->with('success', 'تم إضافة المادة بنجاح');
    }

    public function showBarcode(Product $product)
    {
        return view('products.barcode', compact('product'));
    }

    public function printAllBarcodes()
    {
        $products = Product::orderBy('name')->get();
        return view('products.print-all', compact('products'));
    }
}