<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        $locationId = $request->get('location_id');
        $selectedLocation = $locationId ? Location::find($locationId) : null;

        // إذا كان المستخدم مرتبط بمخزن معين، استخدمه كافتراضي
        $userLocationId = auth()->user()->location_id;
        if (!$locationId && $userLocationId) {
            $locationId = $userLocationId;
            $selectedLocation = Location::find($userLocationId);
        }

        $query = Product::orderBy('name');

        if ($locationId) {
            $query = $query->whereHas('stockBatches', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })->with([
                'stockBatches' => function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                },
                'locationThresholds' => function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                },
            ]);
        } else {
            $query = $query->with('stockBatches');
        }

        $products = $query->paginate(25)->withQueryString();

        return view('inventory.index', compact('products', 'locations', 'locationId', 'selectedLocation'));
    }

    public function lowStock(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        $locationId = $request->get('location_id');
        $selectedLocation = $locationId ? Location::find($locationId) : null;

        // إذا كان المستخدم مرتبط بمخزن معين، استخدمه كافتراضي
        $userLocationId = auth()->user()->location_id;
        if (!$locationId && $userLocationId) {
            $locationId = $userLocationId;
            $selectedLocation = Location::find($userLocationId);
        }

        $products = Product::with([
            'stockBatches' => function ($q) use ($locationId) {
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            },
            'locationThresholds' => function ($q) use ($locationId) {
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            },
        ])->orderBy('name')->get();

        $lowStockProducts = $products->filter(function ($product) use ($locationId) {
            $totalQty = $product->stockBatches->sum('current_qty');
            $alertQty = $product->getAlertQuantityForLocation($locationId);

            return $totalQty <= $alertQty;
        });

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $paginatedProducts = new LengthAwarePaginator(
            $lowStockProducts->forPage($page, $perPage)->values(),
            $lowStockProducts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('inventory.low_stock', [
            'products' => $paginatedProducts,
            'locations' => $locations,
            'locationId' => $locationId,
            'selectedLocation' => $selectedLocation,
        ]);
    }
}
