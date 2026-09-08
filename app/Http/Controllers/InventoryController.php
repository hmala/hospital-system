<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatch;
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
            $query = $query->with(['stockBatches', 'locationThresholds']);
        }

        // حساب الإحصائيات الشاملة لجميع المواد عبر الاستعلام الكامل وليس الصفحة الأولى فقط
        $allProductsForStats = (clone $query)->get();

        $totalProductsCount = $allProductsForStats->count();

        $availableProductsCount = $allProductsForStats->filter(function ($product) {
            return $product->stockBatches->sum('current_qty') > 0;
        })->count();

        $lowStockCount = $allProductsForStats->filter(function ($product) use ($locationId) {
            $alertQty = $product->getAlertQuantityForLocation($locationId);
            $totalQty = $product->stockBatches->sum('current_qty');
            return $alertQty > 0 && $totalQty <= $alertQty;
        })->count();

        $criticalStockCount = $allProductsForStats->filter(function ($product) use ($locationId) {
            $reorderQty = $product->getReorderLevelForLocation($locationId);
            $totalQty = $product->stockBatches->sum('current_qty');
            return $reorderQty > 0 && $totalQty <= $reorderQty;
        })->count();

        $products = $query->paginate(25)->withQueryString();

        return view('inventory.index', compact(
            'products',
            'locations',
            'locationId',
            'selectedLocation',
            'totalProductsCount',
            'availableProductsCount',
            'lowStockCount',
            'criticalStockCount'
        ));
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

    /**
     * شاشة تقرير وتنبيهات صلاحية المواد والوجبات المخزنية (FEFO Tracking)
     */
    public function expiring(Request $request)
    {
        $locations = Location::orderBy('name')->get();
        $locationId = $request->get('location_id');
        $selectedLocation = $locationId ? Location::find($locationId) : null;

        $userLocationId = auth()->user()->location_id;
        if (!$locationId && $userLocationId) {
            $locationId = $userLocationId;
            $selectedLocation = Location::find($userLocationId);
        }

        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = StockBatch::with(['product', 'location', 'purchaseItem.purchase'])
            ->where('current_qty', '>', 0)
            ->whereNotNull('expiry_date');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('internal_barcode', 'like', "%{$search}%")
                  ->orWhere('original_barcode', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('purchaseItem.purchase', function ($pr) use ($search) {
                      $pr->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        // حساب الإحصائيات الشاملة قبل تطبيق فلتر الحالة
        $statsQuery = clone $query;
        $allBatches = $statsQuery->get();

        $today = now()->startOfDay();
        $in30Days = now()->addDays(30)->endOfDay();
        $in90Days = now()->addDays(90)->endOfDay();

        $expiredBatches = $allBatches->where('expiry_date', '<=', $today);
        $expiredCount = $expiredBatches->count();
        $expiredValue = $expiredBatches->sum(fn($b) => $b->current_qty * $b->cost_price);

        $criticalBatches = $allBatches->filter(fn($b) => $b->expiry_date > $today && $b->expiry_date <= $in30Days);
        $criticalCount = $criticalBatches->count();
        $criticalValue = $criticalBatches->sum(fn($b) => $b->current_qty * $b->cost_price);

        $warningBatches = $allBatches->filter(fn($b) => $b->expiry_date > $in30Days && $b->expiry_date <= $in90Days);
        $warningCount = $warningBatches->count();
        $warningValue = $warningBatches->sum(fn($b) => $b->current_qty * $b->cost_price);

        $validBatches = $allBatches->where('expiry_date', '>', $in90Days);
        $validCount = $validBatches->count();

        // تطبيق فلتر الحالة المحددة
        if ($status === 'expired') {
            $query->where('expiry_date', '<=', $today);
        } elseif ($status === 'critical_30') {
            $query->where('expiry_date', '>', $today)->where('expiry_date', '<=', $in30Days);
        } elseif ($status === 'warning_90') {
            $query->where('expiry_date', '>', $in30Days)->where('expiry_date', '<=', $in90Days);
        } elseif ($status === 'valid') {
            $query->where('expiry_date', '>', $in90Days);
        }

        $batches = $query->orderBy('expiry_date', 'asc')->paginate(25)->withQueryString();

        return view('inventory.expiring', compact(
            'batches',
            'locations',
            'locationId',
            'selectedLocation',
            'status',
            'search',
            'expiredCount',
            'expiredValue',
            'criticalCount',
            'criticalValue',
            'warningCount',
            'warningValue',
            'validCount'
        ));
    }
}
